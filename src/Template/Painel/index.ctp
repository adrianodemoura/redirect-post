<div class="row" style="margin: 0px auto; margin-top: 60px; width: 500px;">
<?php

    echo $this->Form->create($PainelForm);

    // campo nome
    echo $this->Form->control('nome');

    // campo cpf
    echo $this->Form->control('cpf');

    echo $this->Form->submit('Enviar');

    echo $this->Form->end();

?>
</div>