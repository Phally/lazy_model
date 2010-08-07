<?php
abstract class LazyModel extends Model {

	private $map = array();

	public function __construct($id = false, $table = null, $ds = null) {
		foreach (array('hasMany', 'belongsTo', 'hasOne') as $type) {
			foreach ((array)$this->{$type} as $key => $properties) {
				$this->map($key, $properties);
			}
		}
		parent::__construct($id, $table, $ds);
	}
	
	public function __constructLinkedModel($assoc, $className = null) {
		if (!isset($this->map[$assoc])) {
			parent::__constructLinkedModel($assoc, $className);
		}
	}
	
	public function __isset($alias) {
		return isset($this->map[$alias]);
	}
	
	public function __get($alias) {
		if (!property_exists($this, $alias) && isset($this->map[$alias])) {
			$this->constructLazyLinkedModel($alias, $this->map[$alias]);
			return $this->{$alias};
		}
	}
	
	private function constructLazyLinkedModel($assoc, $className = null) {
		if (empty($className)) {
			$className = $assoc;
		}
		$this->{$assoc} = ClassRegistry::init(array('class' => $className, 'alias' => $assoc));
		if (strpos($className, '.') !== false) {
			ClassRegistry::addObject($className, $this->{$assoc});
		}
		if ($assoc) {
			$this->tableToModel[$this->{$assoc}->table] = $assoc;
		}
	}
	
	private function map($key, $properties) {
		if (is_numeric($key)) {
			list($plugin, $alias) = pluginSplit($properties);
			$properties = array('className' => $properties);
		} else {
			$alias = $key;
			if (!isset($properties['className'])) {
				$properties['className'] = $alias;
			}
		}
		$this->map[$alias] = $properties['className'];
	}
}
?>