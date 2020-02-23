<?php ?>

<div style="font-size: 10px;">

<?php debug($data); ?>

</div>
<div>
	<?= $this->Html->link('Voltar', ['action'=>'index', $this->request->pass[0]], ['class'=>'button'] ); ?>
	<?= $this->Html->link('Limpar', ['action'=>'limpar',$this->request->pass[0]], ['class'=>'button'] ); ?>
</div>