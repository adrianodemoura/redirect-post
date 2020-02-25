<?php
/**
 * Component Redirect
 *
 * @package     redirectPost.Controller.Component
 * @author      Adriano Moura
 */
namespace RedirectPost\Controller\Component;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
/**
 * Maintains the redirect componente of the RedirectPost plugin.
 */
class RedirectComponent extends Component
{
    /**
     * Sufix to key.
     *
     * @var     string
     */
    private $sufix      = 'Redirect';

    /**
     * Key of the form.
     * The default nomenclauture is "RedirectPost.PluginName.ControllerName"
     *
     * @var Integer
     */
    private $key        = '';

    /**
     * Tim do expiration of the form, in minutes.
     *
     * @var Integer
     */
    private $time       = 20;

    /**
     * Storage default.
     * cookie|session|file.
     *
     * if file, make the sure that directo "tmp/cache/redirect" exists.
     * 
     * @var     string
     */
    private $storage    = 'cookie';

    /**
     * Form serial.
     * This atribute must be informed by the GET.
     * He is a unique key of the form.
     *
     * @var     Integer
     */
    private $serialForm = 0;

    /**
     * Componentes
     * 
     * @var     Array
     */
    public $components  = ['Cookie'];

    /**
     * hook method.
     * 
     * @param   array   $config         Component settings: sufix, key, expiration time, storage.
     * @return  void
     */
    public function initialize( array $config=[] )
    {
        $this->Controller   = $this->_registry->getController();
        $plugin             = $this->Controller->getPlugin();

        $this->time         = isset( $config['time'] )      ? $config['time']                   : $this->time;

        $this->storage      = isset( $config['storage'] )   ? strtolower($config['storage'])    : $this->storage;

        $this->sufix        = isset( $config['sufix'] )     ? strtolower($config['sufix'])      : $this->sufix;

        $key                = $this->sufix.'.'; if ( !empty($plugin) ) { $key .= $plugin.'.'; }
        $key                .= $this->Controller->getName();
        $this->key          = $key;

        $this->serialForm   = @$this->Controller->request->getParam('pass')[0];

        $this->Controller->set( 'sufix', $this->sufix );
        $this->Controller->set( 'key', $this->key );
        $this->Controller->set( 'serialForm', $this->serialForm );
    }

    /**
     * Return information of the form in cache..
     *
     * @return  Array   $info   time and storage of the component.
     */
    public function info( $key='' )
    {
        $data       = $this->read( $key, true );
        $criado     = @$data['time_created_form'];
        $expirado   = $criado + ($this->time * 60);
        $diff       = $expirado - time();

        return
        [
            'chave'             => $this->key,
            'serialForm'        => $this->serialForm,
            'storage'           => $this->storage, 
            'created'           => date( 'H:i:s', $criado ), 
            'expired'           => date( 'H:i:s', $expirado ), 
            'time to expiration'=> date( 'i:s', $diff ),
            'expiration time (minutes)'   => $this->time,
            'data'              => $data
        ];
    }

    /**
     * Execute the redirection and save the form to cache.
     * 
     * @param   mixed   $url    redirect parameters.
     * @param   array   $data   form data to be saved in cache.
     * @return  void
     */
    public function saveRedirect($url=null, $data=[])
    {
        $time   = time();
        $key  = $this->key.".".$time;

        if ( $this->serialForm > 0 )
        {
            $this->delete( $this->key.'.'.$this->serialForm );
        }

        switch ($this->storage)
        {
            case 'file':
                $file   = strtolower( str_replace('.','_',$key) );
                $dir    = TMP . "cache". DS. strtolower( $this->sufix );
                $fp     = @fopen( $dir . DS . $file, "w");

                if ( !$fp )
                {
                    if ( !mkdir($dir) )
                    {
                        throw new \Exception( __("Não foi possível criar o diretório $dir"), 1);
                    }
                    $fp = @fopen($dir.DS.$file, "w");
                }

                if ( !$fp )
                {

                    throw new \Exception( __("Não foi possível abrir o arquivo $dir".DS."$file. Verifique se possui permissão de leitura !"), 2 );
                }
                
                fwrite( $fp, json_encode( ['data'=>$data, 'time'=>$time] ) );
                fclose( $fp );
            break;

            case 'cookie':
                $this->Cookie->write( str_replace('.','_',$key), ['data'=>$data, 'time'=>$time] );
            break;

            default:
                $Sessao = $this->Controller->request->getSession();
                $Sessao->write( $key, ['data'=>$data, 'time'=>$time] );
        }

        if ( is_array($url) )
        {
            $url[] = $time;
        } else
        {
            $url .= "/".$time;
        }

        return $this->Controller->redirect( $url );
    }

    /**
     * Delete a form from cache.
     * 
     * @param   string          $key            Form key.
     * @return  void
     */
    public function delete( $key='' )
    {
        $key  = empty( $key ) ? $this->key . '.' . $this->serialForm : $key;

        switch ( $this->storage )
        {
            case 'file':
                $key = str_replace( '.', '_', $key);
                @unlink( TMP . "cache". DS. strtolower($this->sufix) . DS . strtolower( $key ) );
            break;

            case 'cookie':
                $key = str_replace( '.', '_', $key);
                $this->Cookie->delete( $key );
            break;

            default:
                $plugin     = $this->Controller->plugin;
                $name       = $this->Controller->name;
                $Sessao     = $this->Controller->request->getSession();

                $Sessao->delete( $key );

                if ( empty( $Sessao->read( $this->sufix . '.'. $plugin . '.' . $name) ) )
                {
                    $Sessao->delete( $this->sufix . '.' . $plugin . '.' . $name );
                }
                if ( empty( $Sessao->read( $this->sufix . '.' . $plugin ) ) )
                {
                    $Sessao->delete( $this->sufix . '.' . $plugin );
                }
                if ( empty( $Sessao->read( $this->sufix ) ) )
                {
                    $Sessao->delete( $this->sufix );
                }
        }

        return true;
    }

    /**
     * Return data of the form.
     * 
     * @param   string          $key            Form key.
     * @param   boolean         $insertCreate   If true, return date of the form too. If False don't.
     * @return  array|boolean   $data           If time expired, return False, eitheir Array.
     */
    public function read( $key='', $insertCreated=false )
    {
        switch ( $this->storage )
        {
            case 'file':
                return $this->getFile( $key, $insertCreated );
            break;

            case 'cookie':
                return $this->getCookie( $key, $insertCreated );
            break;

            default:
                return $this->getSession( $key, $insertCreated );
        }
    }

    /**
     * Return the form from session cache.
     * 
     * @param   String          $key            Form key.
     * @param   Boolean         $insertCreate   If True, return time criation the form.
     * @return  False|Array     $data           If time of the form expired, return False, either return Array.
     */
    private function getSession( $key='', $insertCreated=false )
    {
        $key      = empty( $key ) ? $this->key . '.' . $this->serialForm : $key;
        $Sessao     = $this->Controller->request->getSession();
        $dados      = $Sessao->read( $key );
        $data       = @$dados['data'];
        $expiracao  = ((time() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            $Sessao->delete( $key );
            if ( empty($Sessao->read( $this->sufix )) ) 
            {
                $Sessao->delete( $this->sufix );
            }
            $data = [];
        } elseif ( $insertCreated )
        {
            $data['time_created_form'] = $dados['time'];
        }

        return $data;
    }

    /**
     * Return the form from file cache.
     * 
     * @param   String          $key            Form key.
     * @param   Boolean         $insertCreate   If True, return time criation the form.
     * @return  Boolean|Array   $data           If time of the form expired, return False, either return Array.
     */
    private function getFile( $key='', $insertCreated=false )
    {
        $key      = empty( $key ) ? $this->key.".".$this->serialForm : $key;
        $data       = [];
        $file       = strtolower( str_replace('.','_',$key) );
        $dir        = TMP . "cache" . DS . strtolower( $this->sufix );
        $dados      = @json_decode( file_get_contents( $dir . DS . $file ), true );
        $data       = @$dados['data'];
        $expiracao  = round( (time() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            @unlink( $dir . DS . $file );
            $data = [];
        } elseif ( $insertCreated )
        {
            $data['time_created_form'] = $dados['time'];
        }

        return $data;
    }

    /**
     * Return the form from cookie cache.
     *
     * @param   string          $key            Form key.
     * @param   boolean         $insertCreate   If True, return time criation the form.
     * @return  boolean|array   $data           If time of the form expired, return False, either return Array.
     */
    private function getCookie( $key='', $insertCreated=false )
    {
        $key      = empty( $key ) ? $this->key.".".$this->serialForm : $key;
        $key      = str_replace('.','_', $key);

        $dados      = $this->Cookie->read( $key );

        $data       = @$dados['data'];

        $expiracao  = ((time() - @$dados['time']) / 60);

        if ( $expiracao > $this->time || empty($data) )
        {
            $this->delete( $key );
            $data = [];
        } elseif ( $insertCreated )
        {
            $data['time_created_form'] = $dados['time'];
        }

        return $data;
    }
}