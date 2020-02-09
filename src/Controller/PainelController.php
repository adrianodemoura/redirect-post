<?php
namespace RedirectPost\Controller;

use RedirectPost\Controller\AppController;

/**
 * Painel Controller
 *
 *
 * @method \RedirectPost\Model\Entity\Painel[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PainelController extends AppController
{
    /**
     * Método de inicialização
     */
    public function initialize()
    {
        parent::initialize();
        //$this->loadComponent('RedirectPost.Redirect', ['storage'=>'cache']);
        $this->loadComponent('RedirectPost.Redirect');
    }

    /**
     * Método inicial
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
        //$this->Redirect->delete();

        $this->set( compact('data') );
    }
}
