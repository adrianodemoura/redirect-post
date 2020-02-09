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
        //$this->loadComponent( 'RedirectPost.Redirect', ['storage'=>'session', 'time'=>10] );
        //$this->loadComponent('RedirectPost.Redirect', ['storage'=>'cache', 'time'=>15]);
        $this->loadComponent('RedirectPost.Redirect');
    }

    /**
     * Método inicial, que irá exibir o formulário.
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $data       = $this->request->getData();
        $PainelForm = new PainelForm();

        if ( $this->request->isPost() )
        {
            if ( $PainelForm->execute( $data ) )
            {
                $this->Redirect->save( ['action'=>'visualizar'], $data);
            }
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

        if ( !$data )
        {
            $this->Flash->error( __('Parâmetros inválidos !') );
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
        $this->Redirect->delete();
        $this->Flash->success( __('O Cache foi limpo com sucesso') );

        return $this->redirect( ['action'=>'index'] );
    }
}
