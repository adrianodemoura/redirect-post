<div class="row" style="margin: 0px auto; margin-top: 60px; width: 500px;">
<?php

    echo $this->Form->create($PainelForm);

    echo $this->Form->control('placa');

    echo $this->Form->control('cpf');

    echo $this->Form->submit('Enviar');

    echo $this->Form->end();

?>
</div>