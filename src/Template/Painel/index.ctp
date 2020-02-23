
<div class="row" style="margin: 0px auto; margin-top: 60px; width: 500px;">

<?php

    echo $this->Form->create($PainelForm);

    echo $this->Form->control('nome');

    echo $this->Form->control('cpf');

    echo "<div>";

    echo $this->Form->button('Enviar', ['type'=>'submit', 'class'=>'button'] );

    if ( $serialPost )
    {
        echo "&nbsp;".$this->Html->link('Limpar', ['action'=>'limpar', $serialPost], ['class'=>'button'] );
    }

    echo "</div>";

    echo $this->Form->end();

?>

</div>