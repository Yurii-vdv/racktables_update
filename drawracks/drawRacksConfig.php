<?php
// Draw Racks to Excel 
// Config file (for Japanese)

// 2016-04-04 - Hipontire <miyoshi@outlook.com>
// 2021-11-01 - yranin@gmail.com
global $drawracks_conf;
$drawracks_conf = array (
	'templatefile'		=> "../plugins/drawracks/xlsx/drawracks.xlsx",
	'title'			=> "Rackspace",
	'location_name_label'	=> "Место установки",
	'row_name_label'	=> "Название столбца",
	'name_label'		=> "стойка",
	'front_label'		=> "Передний",
	'interior_label'	=> "внутренний",
	'back_label'		=> "назад",
	'export_button'		=> "Импортировать в Excel",
	'empty_now'			=> "(Без регистрации)",
	'empty_row'			=> "(Нет зарегистрированных столбцов)",
	'file_not_found'	=> "Файл не существует",
	'not_specified'		=> "Стойка не указана",
	'bgstate_F'			=> "afcfcf",
	'bgstate_A'			=> "cfcfcf",
	'bgstate_U'			=> "cfafaf",
	'bgstate_T'			=> "70a0a0",
	'bgcell_border'			=> "707070"
);
$tab['reports']['rack'] = 'Rackspace';
?>
