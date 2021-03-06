<?php

namespace Tag;

class TagInstaller extends \Reborn\Module\AbstractInstaller
{

	public function install($prefix = null) 
	{
		\Schema::table($prefix.'tags', function($table)
		{
			$table->create();
			$table->increments('id');
			$table->string('name',50);
		});

		\Schema::table($prefix.'tags_relationship', function($table)
		{
			$table->create();
			$table->increments('id');
			$table->integer('tag_id');
			$table->integer('object_id');
			$table->string('object_name', 32);
		});
	}

	public function uninstall($prefix = null) 
	{
		\Schema::drop($prefix.'tags');
		\Schema::drop($prefix.'tags_relationship');
	}

	public function upgrade($v, $prefix = null) 
	{
		return $v;
	}

}
