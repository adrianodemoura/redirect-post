<?php

	echo $this->Html->scriptBlock("var chave='".$chave.'.'.$serialForm."';\n", ['block'=>false]);

	echo $this->Html->script( ['RedirectPost./js/limpar'], ['block'=>true]);

