<?php
	//Include the php18 class
	include('php18.class.php');

	//Load swedish as default, and english as fallback
	$l = new php18('se', 'en', 'example');

	echo lc::string1; //Will print the swedish translation

	echo lc::string2; //WIll print english, because there is no swedish translation

	echo lc::group1_test; //Multi level print
