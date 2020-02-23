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
 * Mantém o componente redirect do plugin RedirectPost.
 */
class RedirectComponent extends Component
{
    /**
     * Chave a ser usada para guardados os dados no storage.
     * O Padrão é "Redirect.{NomePlugin}{NomeController}"
     *
     * @var Integer
     */
    private $chave      = '';

    /**
     * Tempo para a experição dos dados em minutos.
     *
     * @var Integer
     */
    private $time       = 20;

    /**
     * Storage do dados, pode ser "session" ou "cache".
     * No caso de cache o componente irá o diretório "tmp/cache/redirectPost" do sistema, certifique-se que o diretório foi criado e possua permissão de escrita.
     * 
     * @var     String
     */
    private $storage    = 'cookie';

    /**
     * Serial do formulário, repassado via GET.
     * Este serial é chave única para cada formulário gerado.
     * Através dele é possível repetir o formulário várias vezes.
     *
     * @var     Integer
     */
    private $serialPost = 0;

    /**
     * Componentes
     * 
     * @var     Array
     */
    public $components  = ['Cookie'];

    /**
     * Método de inicilização do componente.
     * 
     * @param   array   $config     Configurações do componente. chave, time e storage.
     * @return  \Cake\Http\Response|null
     */
    public function initialize( array $config=[] )
    {
        $this->Controller   = $this->_registry->getController();
        $plugin             = $this->Controller->getPlugin();

        $chave              = 'CachePost.';
        if ( !empty($plugin) ) { $chave .= $plugin.'.'; }
        $chave              .= $this->Controller->getName();
        $this->chave        = $chave;

        $this->time         = isset( $config['time'] ) ? $config['time'] : $this->time;

        $this->storage      = isset( $config['storage'] ) ? strtolower($config['storage']) : $this->storage;

        $this->serialPost   = @$this->Controller->request->getParam('pass')[0];

        $this->Controller->set( 'chave', $this->chave );
        $this->Controller->set( 'serialPost', $this->serialPost );
    }

    /**
     * Return information of Redirect.
     *
     * @return  Array   $info   time and storage of the component.
     */
    public function info()
    {
        $data       = $this->read();
        $criado     = @$data['time_created_post'];
        $expirado   = @$data['time_created_post'] + ($this->time * 60);
        $diff       = $expirado - mktime();

        return
        [
            'chave'             => $this->chave,
            'serialPost'        => $this->serialPost,
            'storage'           => $this->storage, 
            'created'           => date( 'H:i:s', $criado ), 
            'expired'           => date( 'H:i:s', $expirado ), 
            'time to expiration'=> date( 'i:s', $diff ),
            'expiration time (minutes)'   => $this->time,
            'data'              => $data
        ];
    }

    /**
     * Executa o redirecionamento a salva na sessão os dados de $data.
     * 
     * @param   mixed   $url    Parâmetros do redirecionamento, pode ser uma string ou ums array, veja mais parâmetros do método redirect.
     * @param   Array   $data   Dados a serem salvos.
     * @return  \Cake\Http\Response|Null
     */
    public function saveRedirect($url=null, $data=[])
    {
        $time   = mktime();
        $chave  = $this->chave.".".$time;

        if ( $this->serialPost > 0 )
        {
            $this->delete( $this->chave.'.'.$this->serialPost );
        }

        switch ($this->storage)
        {
            case 'cache':
                $file   = strtolower( str_replace('.','_',$chave) );
                $dir    = TMP . "cache". DS. "redirectPost";
                $fp     = @fopen($dir.DS.$file, "w");

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
                //setcookie( $chave, json_encode( ['data'=>$data, 'time'=>$time] ), strtotime( '+'+$this->time+' minutes'), $this->Controller->request->base.'/' );
                $this->Cookie->write( str_replace('.','_',$chave), ['data'=>$data, 'time'=>$time] );
            break;

            default:
                $Sessao = $this->Controller->request->getSession();
                $Sessao->write( $chave, ['data'=>$data, 'time'=>$time] );
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
     * Retorna os dados de um redirectPost
     * 
     * @param   Integer         $keyPost        Chave do formulário.
     * @param   Boolean         $insertTime     Se verdadeiro retorna o tempo de expiração, Falso não.
     * @return  Array|Boolean   $data           Falso se o dado foi expirado, Array se não.
     */
    public function read( $chave='' )
    {
        switch ( $this->storage )
        {
            case 'cache':
                return $this->getCache( $chave );
            break;

            case 'cookie':
                return $this->getCookie( $chave );
            break;

            default:
                return $this->getSession( $chave );
        }
    }

    /**
     * Exclui o RedirectPost
     * 
     * @param   Integer     $keyPost    Serial do formulário.
     * @return  \Cake\Http\Response|Null
     */
    public function delete( $chave='' )
    {
        $chave  = empty( $chave ) ? $this->chave.".".$this->serialPost : $chave;
        $chave  = str_replace('.','_', $chave);

        switch ( $this->storage )
        {
            case 'cache':
                @unlink( TMP . "cache". DS. "redirectPost" . DS . strtolower( $chave ) );
            break;

            case 'cookie':
                $this->Cookie->delete( $chave );
            break;

            default:
                $plugin     = $this->Controller->plugin;
                $name       = $this->Controller->name;
                $Sessao     = $this->Controller->request->getSession();

                $Sessao->delete( $chave );

                if ( empty($Sessao->read('CachePost.'.$plugin.'.'.$name)) )
                {
                    $Sessao->delete('CachePost.'.$plugin.'.'.$name);
                }
                if ( empty($Sessao->read('CachePost.'.$plugin)) )
                {
                    $Sessao->delete('CachePost.'.$plugin);
                }
                if ( empty($Sessao->read('CachePost')) )
                {
                    $Sessao->delete('CachePost');
                }
        }

        return true;
    }

    /**
     * Retorna os dados do Cache.
     * 
     * @param   String          $chave      Chave do formulário.
     * @return  False|Array     $data       Falso se o tempo expirou, Array se não.
     */
    private function getSession( $chave='' )
    {
        $chave      = empty( $chave ) ? $this->chave.".".$this->serialPost : $chave;
        $Sessao     = $this->Controller->request->getSession();
        $dados      = $Sessao->read( $chave );
        $data       = @$dados['data'];
        $expiracao  = ((mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            $Sessao->delete( $chave );
            if ( empty($Sessao->read('RedirectPost')) ) 
            {
                $Sessao->delete('RedirectPost');
            }
            $data = [];
        } else
        {
            $data['time_created_post'] = $dados['time'];
        }

        return $data;
    }

    /**
     * Retorna os dados do Cache.
     * 
     * @param   String          $chave      Chave do formulário.
     * @return  Boolean|Array   $data       Falso se o tempo expirou, Array se não.
     */
    private function getCache( $chave='' )
    {
        $chave      = empty( $chave ) ? $this->chave.".".$this->serialPost : $chave;
        $data       = [];
        $file       = strtolower( str_replace('.','_',$chave) );
        $dir        = TMP . "cache" . DS . "redirectPost";
        $dados      = @json_decode( file_get_contents( $dir . DS . $file ), true );
        $data       = @$dados['data'];
        $expiracao  = round( (mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            @unlink( $dir . DS . $file );
            $data = [];
        } else
        {
            $data['time_created_post'] = $dados['time'];
        }

        return $data;
    }

    /**
     * Retorna os dados do Cookie.
     *
     * @param   String          $chave      Chave do formulário.
     * @return  Boolean|Array   $data       Falso se o tempo expirou, Array se não.
     */
    private function getCookie( $chave='' )
    {
        $chave      = empty( $chave ) ? $this->chave.".".$this->serialPost : $chave;
        $chave      = str_replace('.','_', $chave);

        //$dados      = @json_decode( $_COOKIE[ $chave ], true );
        $dados      = $this->Cookie->read( $chave );

        $data       = @$dados['data'];
        $expiracao  = ((mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time || empty($data) )
        {
            unset( $_COOKIE[$this->chave] );
            $data = [];
        } else
        {
            $data['time_created_post'] = @$dados['time'];
        }

        return $data;
    }
}