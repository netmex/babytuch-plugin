<?php


namespace Inc\Models;


use ReflectionProperty;

abstract class Entity {
	protected static array $attributes;
	protected static string $table_name;

	protected ?int $id = null;

	/**
	 * @throws \ReflectionException
	 */
	public function save() {
		global $wpdb;
		$attributeUpdates = [];
		foreach(static::$attributes as $attribute) {
			$property = new ReflectionProperty(get_class($this), $attribute);
			$property->setAccessible(true);
			if($property->isInitialized($this)) {
				$attributeUpdates[] = '`'.$attribute.'` = "'.$this->{$attribute}.'"';
			}
		}

		$setClause = implode(',', $attributeUpdates);

		if($this->id) {
			$sqlQuery = 'UPDATE `'.static::$table_name.'` SET '.$setClause.' WHERE id = '.$this->id;
		} else {
			$sqlQuery = 'INSERT INTO `'.static::$table_name.'` SET '.$setClause;
		}

		$wpdb->query($sqlQuery);
		if(!$this->id && $wpdb->insert_id ) {
			$this->id = $wpdb->insert_id;
		}

	}

}