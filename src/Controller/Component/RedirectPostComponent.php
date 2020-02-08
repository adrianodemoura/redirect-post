<?php
/**
 * Componente RedirectPost
 * 
 * load in Controller:
 * $this->loadComponent('RedirectPost', ['storage'=>'session', 'time'=>100]);
 * 
 * usage in Controller:
 * $this->RedirectPost->save( ['action'=>'action_target'], $data);
 * $data = $this->RedirectPost->read();
 * $this->RedirectPost->delete();
 */
namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
/**
 * Mantém o componente RedirectPost.
 */
class RedirectPostComponent extends Component
{
    /**
     * Chave a ser usada para guardados os dados no storage.
     * O Padrão é Redirect.PluginController
     *
     * @var Integer
     */
    private $chave      = '';

    /**
     * Tempo para a experição dos dados em minutos.
     *
     * @var Integer
     */
    private $time       = 60;

    /**
     * Storage do dados, pode ser "session" ou "cache".
     * 
     * @var     String
     */
    private $storage    = 'session';

    /**
     * Método de inicilização do componente.
     * 
     * ### Config
     * 
     * @param   array   $config     Configurações do componente. chave, time e storage.
     * @return  \Cake\Http\Response|null
     */
    public function initialize( array $config=[] )
    {
        $this->chave    = 'RedirectPost.'.$this->_registry->getController()->plugin.''.$this->_registry->getController()->name;

        $this->time     = isset( $config['time'] ) ? $config['time'] : $this->time;

        $this->storage  = isset( $config['storage'] ) ? strtolower($config['storage']) : $this->storage;
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
        
        switch ($this->storage)
        {
            case 'session':
                $Sessao = $this->_registry->getController()->request->getSession();
                $Sessao->write( $this->chave, ['data'=>$data, 'time'=>mktime()] );
            break;

            case 'cache':
                $file   = strtolower( str_replace('RedirectPost.','',$this->chave) );
                $dir    = TMP . "cache". DS. "redirectPost";
                $fp = @fopen($dir.DS.$file, "w");
                if ( !$fp )
                {
                    throw new \Exception( __("Não foi possível abrir o arquivo $dir".DS."$file. Verifique se possui permissão de leitura !") );
                }
                fwrite( $fp, json_encode( ['data'=>$data, 'time'=>mktime()] ) );
                fclose($fp);
            break;
        }
        
        return $this->_registry->getController()->redirect( $url );
    }

    /**
     * Retorna os dados de um redirectPost
     * 
     * @return  Array|Boolean   Falso se o dado foi expirado, Array se não.
     */
    public function read()
    {
        switch ($this->storage)
        {
            case 'session':
                return $this->getSession();
            case 'cache':
                return $this->getCache();
            break;
        }
    }

    /**
     * Exclui o RedirectPost
     * 
     * @return  \Cake\Http\Response|Null
     */
    public function delete()
    {
        switch ($this->storage)
        {
            case 'session':
                $Sessao = $this->_registry->getController()->request->getSession();
                $Sessao->delete( $this->chave );
            break;

            case 'cache':
                $file   = strtolower( str_replace('RedirectPost.','',$this->chave) );
                $dir    = TMP . "cache". DS. "redirectPost";
                unlink( $dir . DS . $file );
            break;
        }
        return true;
    }

    /**
     * Retorna os dados do Cache.
     * 
     * @return  False|Array     Falso se o tempo expirou, Array se não.
     */
    private function getSession()
    {
        $Sessao     = $this->_registry->getController()->request->getSession();
        $dados      = $Sessao->read( $this->chave );
        $data       = @$dados['data'];
        $expiracao  = round( (mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            $Sessao->delete( $this->chave );
            $data = false;
            if ( empty($Sessao->read('RedirectPost')) ) 
            {
                $Sessao->delete('RedirectPost');
            }
        }

        return $data;
    }

    /**
     * Retorna os dados do Cache.
     * 
     * @return  False|Array     Falso se o tempo expirou, Array se não.
     */
    private function getCache()
    {
        $data       = false;
        $file       = strtolower( str_replace('RedirectPost.','',$this->chave) );
        $dir        = TMP . "cache". DS. "redirectPost";
        $dados      = @json_decode( file_get_contents( $dir.DS.$file ), true );
        $data       = @$dados['data'];
        $expiracao  = round( (mktime() - @$dados['time']) / 60);

        if ( $expiracao > $this->time )
        {
            @unlink( $dir . DS . $file );
            $data = false;
        }

        return $data;
    }
}

?>