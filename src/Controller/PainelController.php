<?php
/**
 * Controller Painel
 *
 * @package     redirectPost.Controller
 * @author      Adriano Moura
 */
namespace RedirectPost\Controller;
use RedirectPost\Controller\AppController;
use RedirectPost\Form\PainelForm;
/**
 * Cotnroller para teste do componente Redirect
 */
class PainelController extends AppController
{
    /**
     * Método de inicialização
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent( 'RedirectPost.Redirect' );
    }

    /**
     * Método inicial, que irá exibir o formulário.
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $PainelForm = new PainelForm();

        if ( $this->request->isPost() )
        {
            $data   = $this->request->getData();
            if ( $PainelForm->execute( $data ) )
            {
                $this->Redirect->saveRedirect( ['action'=>'visualizar'], $data);
            }
        } else
        {
            $PainelForm->setData( $this->Redirect->read() );
        }

        $this->set( compact('PainelForm') );
    }

    /**
     * Exibe a tela de visualização do cadastro.
     * 
     * @param   Array   $data   Dados do Formulário
     * @return  \Cake\Http\Response|Null
     */
    public function visualizar()
    {
        $data = $this->Redirect->read();

        if ( empty($data) )
        {
            $info = $this->Redirect->info();
            $this->Flash->error( __("Formulário ".$info['serialPost']." inválido !") );
            return $this->redirect( ['action'=>'index'] );
        }
        $data['info'] = $this->Redirect->info();

        $this->set( compact('data') );
    }

    /**
     * Limpa o cache do plugin redirect.
     *
     * @return  \Cake\Http\Response|Null
     */
    public function limpar()
    {
        $info       = $this->Redirect->info();
        $serialPost = $info['serialPost'];

        $this->Redirect->delete();

        $this->Flash->success( __("O Formulário $serialPost, foi limpo com sucesso.") );
        return $this->redirect( ['action'=>'index'] );
    }
}
