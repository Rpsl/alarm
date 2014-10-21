<?php

    require_once __DIR__.'/../vendor/autoload.php';

    $app = new Silex\Application();

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' =>  realpath( __DIR__.'/../views' ),
    ));

    $app['debug'] = true;

    define('CACHE_DIR',         realpath( __DIR__ . '/../cache'));
    define('CACHE_SETTINGS',    realpath( __DIR__ . '/../cache/') . '/settings.json');
    define('SETTINGS_FILE',     realpath( __DIR__ . '/../settings.yml' ));

    $app->get('/', function () use ( $app )  {


        $presets = load_presets();

        if( !is_dir( CACHE_DIR ) || !is_writable( CACHE_DIR ) )
        {
            throw new \Exception('cache folder error');
        }

        $cache_settings = json_decode( file_get_contents( CACHE_SETTINGS ), true );

        $presets['work'] = array_map( function ( $v ) use ( $cache_settings )
        {
            if( in_array( $v['value'], array_column( $cache_settings['work'], 'value' ) ) )
            {
                $v['checked'] = TRUE;
            }
            else
            {
                $v['checked'] = FALSE;
            }

            return $v;
        }, $presets['work'] );

        $presets['weekend'] = array_map( function ( $v ) use ( $cache_settings )
        {
            if( in_array( $v['value'], array_column( $cache_settings['weekend'], 'value' ) ) )
            {
                $v['checked'] = TRUE;
            }
            else
            {
                $v['checked'] = FALSE;
            }

            return $v;
        }, $presets['weekend'] );



        return $app['twig']->render(
            'index.twig',
            [
                'work'    => $presets['work'],
                'weekend' => $presets['weekend']
            ]
        );

    });

    $app->post('/save', function( \Symfony\Component\HttpFoundation\Request $request ) use( $app ){

        $post = $request->getContent() ;

        $presets = load_presets();

        // holy shit!!
        parse_str( $post,$post );

        $result = [];

        // Зачем эти проверки??
        if( !empty( $post['work'] ) )
        {
            $result['work'] = array_filter( $post['work'], function( $v ) use ( $presets ){

                if( in_array( $v, array_column($presets['work'], 'value') ) )
                {
                    return true;
                }

                return false;
            });

            $result['work'] = parse_hours( $result['work'] );
        }

        // Зачем эти проверки??
        if( !empty( $post['weekend'] ) )
        {
            $result['weekend'] = array_filter( $post['weekend'], function( $v ) use ( $presets ){

                if( in_array( $v, array_column( $presets['weekend'], 'value' ) ) )
                {
                    return true;
                }

                return false;
            });

            $result['weekend'] = parse_hours( $result['weekend'] );
        }

        if( file_put_contents( CACHE_SETTINGS, json_encode( $result ), LOCK_EX ) )
        {
            return json_encode(['ok' => true]);
        }

        return json_encode(['bad' => true]);


    });

    $app->get('/cron', function() use ( $app ){

        $cache_settings = json_decode( file_get_contents( CACHE_SETTINGS ), true );

        if( empty( $cache_settings ) )
        {
            die();
        }

        $out = '';

        $tpl = "%d  %d  %s  *   *   %s\n";

        if( !empty( $cache_settings['work'] ) )
        {
            foreach( $cache_settings['work'] as $v )
            {
                $out .= sprintf( $tpl, $v['min'], $v['hour'], '[1-5]', '%RUN_SCRIPT%');
            }
        }

        if( !empty( $cache_settings['weekend'] ) )
        {
            foreach( $cache_settings['weekend'] as $v )
            {
                $out .= sprintf( $tpl, $v['min'], $v['hour'], '[6-7]', '%RUN_SCRIPT%');
            }
        }

        return $out;

    });

    $app->run();


    function load_presets()
    {
        $yml = file_get_contents( SETTINGS_FILE );

        if( empty( $yml ) )
        {
            throw new Exception('read settings file error');
        }

        $settings = Symfony\Component\Yaml\Yaml::parse( $yml );

        if( empty( $settings['presets'] ) || ( empty( $settings['presets']['work'] ) && empty( $settings['presets']['weekend'] ) ) )
        {
            throw new \Exception('configure settings file error');
        }

        $settings['presets']['work']    = parse_hours( ( $settings['presets']['work'] ? $settings['presets']['work'] : [ ] ) );
        $settings['presets']['weekend'] = parse_hours( ( $settings['presets']['weekend'] ? $settings['presets']['weekend'] : [ ] ) );

        return $settings['presets'];
    }

    function parse_hours( array $array )
    {
        $ours = array_map( function( $v ){

            $tmp['hour'] = substr( $v, 0, -2 );
            $tmp['min']  = substr( $v, -2 );
            $tmp['value']= $v;

            return $tmp;

        }, $array );

        return $ours;
    }