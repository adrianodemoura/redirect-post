<?php
/**
 * Form Painel
 */
namespace RedirectPost\Form;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
/**
 * Mantém o formulário de teste do componeten redirect.
 */
class PainelForm extends Form
{
    /**
     * Cria o esquema para os campos do formulário.
     *
     * @param   \Cake\Form\Schema $schema   From schema
     * @return  \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema
            ->addField('nome',  ['type'=>'string', 'length'=>50, 'default'=>'Usuário Teste do Plugin RedirectPost'] )
            ->addField('cpf',   ['type'=>'string', 'length'=>11, 'default'=>'12345678901'] );
    }

    /**
     * Construtor das validações do formulário.
     *
     * @param   \Cake\Validation\Validator  $validator  validador.
     * @return  \Cake\Validation\Validator
     */
    protected function _buildValidator(Validator $validator)
    {
        $validator
            ->add('nome',   'length', ['rule' => ['minLength', 10], 'message' => __('Tamanho inválido para o campo !')] )
            ->add('cpf',    'length', ['rule' => ['minLength', 11], 'message' => __('Tamanho inválido para o campo !')] );

        return $validator;
    }

    /**
     * Defines what to execute once the Form is processed
     *
     * @param array $data Form data.
     * @return bool
     */
    protected function _execute(array $data)
    {
        return $this->validate($data);
    }
}
