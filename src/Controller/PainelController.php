<?php
/**
 * Controller Painel
 *
 * @package     redirectPost.Controller
 * @author      Adriano Moura
 */
namespace RedirectPost\Controller;
use RedirectPost\Controller\AppController;
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
        $Sessao     = $this->request->getSession();
        $PainelForm = new \RedirectPost\Form\PainelForm();

        if ( $this->request->isPost() )
        {
            if ( $PainelForm->execute( $data ) )
            {
                $this->Redirect->save( ['action'=>'visualizar'], $data);
            }
        } else
        {
            //$this->Redirect->delete();
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
}
