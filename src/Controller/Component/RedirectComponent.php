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
     * Método de inicilização do componente.
     * 
     * @param   array   $config     Configurações do componente. chave, time e storage.
     * @return  \Cake\Http\Response|null
     */
    public function initialize( array $config=[] )
    {
        $plugin         = $this->_registry->getController()->getPlugin();

        $chave          = 'CachePost.';
        if ( !empty($plugin) ) { $chave .= $plugin.'.'; }
        $chave          .= $this->_registry->getController()->getName();
        $this->chave    = $chave;

        $this->time     = isset( $config['time'] ) ? $config['time'] : $this->time;

        $this->storage  = isset( $config['storage'] ) ? strtolower($config['storage']) : $this->storage;

        $this->serialPost = @$this->_registry->getController()->request->getParam('pass')[0];
    }

    /**
     * Return information of Redirect.
     *
     * @return  Array   $info   time and storage of the component.
     */
    public function info()
    {
        $data       = $this->read($this->serialPost, true);
        $criado     = $data['time_created_post'];
        $expirado   = $data['time_created_post'] + ($this->time * 60);
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
     * @param   mixed   $url    Parâmetros do redirecionamento, pode ser uma string oum array, veja mais parâmetros do método redirect.
     * @param   Array   $data   Dados a serem salvos.
     * @return  \Cake\Http\Response|Null
     */
    public function save($url=null, $data=[])
    {
        $time   = mktime();
        $chave  = $this->chave.".".$time;
        
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
                setcookie( $chave, json_encode( ['data'=>$data, 'time'=>$time] ), strtotime( '+'+$this->time+' minutes') );
            break;

            default:
                $Sessao = $this->_registry->getController()->request->getSession();
                $Sessao->write( $chave, ['data'=>$data, 'time'=>$time] );
        }

        if ( is_array($url) )
        {
            $url[] = $time;
        } else
        {
            $url .= "/".$time;
        }

        
        return $this->_registry->getController()->redirect( $url );
    }

    /**
     * Retorna os dados de um redirectPost
     * 
     * @param   Integer         $keyPost        Chave do formulário.
     * @param   Boolean         $insertTime     Se verdadeiro retorna o tempo de expiração, Falso não.
     * @return  Array|Boolean   $data           Falso se o dado foi expirado, Array se não.
     */
    public function read($insertTime = false)
    {
        switch ( $this->storage )
        {
            case 'cache':
                return $this->getCache( $insertTime );
            break;

            case 'cookie':
                return $this->getCookie( $insertTime );
            break;

            default:
                return $this->getSession( $insertTime );
        }
    }

    /**
     * Exclui o RedirectPost
     * 
     * @param   Integer     $keyPost    Serial do formulário.
     * @return  \Cake\Http\Response|Null
     */
    public function delete()
    {
        $chave  = $this->chave.".".$this->serialPost;

        switch ( $this->storage )
        {
            case 'cache':
                $file   = strtolower( str_replace('.','_',$chave) );
                $dir    = TMP . "cache". DS. "redirectPost";
                @unlink( $dir . DS . $file );
            break;

            case 'cookie':
                unset($_COOKIE[str_replace('.','_', $chave)]);
                setcookie( $this->chave, '', (time() - 3600) );
            break;

            default:
                $plugin     = $this->_registry->getController()->plugin;
                $Sessao     = $this->_registry->getController()->request->getSession();

                $Sessao->delete( $chave );

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
     * @param   Boolean         $insertTime     Se verdadeiro inclui o tempo de expiração, se falso não.
     * @return  False|Array     $data           Falso se o tempo expirou, Array se não.
     */
    private function getSession( $insertTime = false)
    {
        $chave      = $this->chave.'.'.$this->serialPost;
        $Sessao     = $this->_registry->getController()->request->getSession();
        $dados      = $Sessao->read( $chave );
        $data       = @$dados['data'];
        $expiracao  = ((mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            $Sessao->delete( $chave );
            $data = false;
            if ( empty($Sessao->read('RedirectPost')) ) 
            {
                $Sessao->delete('RedirectPost');
            }
        }

        if ( $insertTime && !empty($data) )
        {
            $data['time_created_post'] = $dados['time'];
        }

        return $data;
    }

    /**
     * Retorna os dados do Cache.
     * 
     * @param   Boolean         $insertTime     Se verdadeiro inclui o tempo de expiração, se falso não.
     * @return  Boolean|Array   $data           Falso se o tempo expirou, Array se não.
     */
    private function getCache( $insertTime = false)
    {
        $chave      = $this->chave.'.'.$this->serialPost;
        $data       = false;
        $file       = strtolower( str_replace('.','_',$chave) );
        $dir        = TMP . "cache" . DS . "redirectPost";
        $dados      = @json_decode( file_get_contents( $dir . DS . $file ), true );
        $data       = @$dados['data'];
        $expiracao  = round( (mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            @unlink( $dir . DS . $file );
            $data = false;
        }

        if ( $insertTime )
        {
            $data['time_created_post'] = $dados['time'];
        }

        return $data;
    }

    /**
     * Retorna os dados do Cookie.
     *
     * @param   Boolean         $insertTime     Se verdadeiro inclui o tempo de expiração, se falso não.
     * @return  Boolean|Array   $data           Falso se o tempo expirou, Array se não.
     */
    private function getCookie( $insertTime = false )
    {
        $chave      = str_replace('.','_', $this->chave)."_".$this->serialPost;
        $dados      = @json_decode( $_COOKIE[ $chave ], true );
        $data       = @$dados['data'];
        $expiracao  = ((mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            unset( $_COOKIE[$this->chave] );
            $data = false;
        }

        if ( $insertTime && !empty($data) )
        {
            $data['time_created_post'] = $dados['time'];
        }

        return $data;
    }
}