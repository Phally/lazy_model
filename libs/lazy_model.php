<?php
abstract class LazyModel extends Model {
	private $map = array();

	public function __construct($id = false, $table = null, $ds = null) {
		foreach ($this->__associations as $type) {
			foreach ((array)$this->{$type} as $key => $properties) {
				if ($type != 'hasAndBelongsToMany') {
					$this->map($key, $properties);
				} elseif (isset($properties['with'])) {
					$this->map(0, (is_array($properties['with'])) ? key($properties['with']) : $properties['with']);
				}
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
		return property_exists($this, $alias) || isset($this->map[$alias]);
	}
	
	public function &__get($alias) {
		if (!property_exists($this, $alias) && isset($this->map[$alias])) {
			$this->constructLazyLinkedModel($alias, $this->map[$alias]);
		}
		return $this->{$alias};
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
		list($alias, $properties) = $return = $this->properties($key, $properties);
		$this->map[$alias] = $properties['className'];
		return $return;
	}

	private function properties($key, $properties) {
		if (is_numeric($key)) {
			list($plugin, $alias) = pluginSplit($properties);
			$properties = array('className' => $properties);
		} else {
			$alias = $key;
			if (!isset($properties['className'])) {
				$properties['className'] = $alias;
			}
		}
		return array($alias, $properties);
	}

	public function bindModel($models, $reset = true) {
		foreach ($models as $type => $data) {
			foreach ($data as $key => $properties) {
				list($alias, $properties) = $this->map($key, $properties);
				if (property_exists($this, $alias)) {
					unset($this->{$alias});
				}
			}
		}
		return parent::bindModel($models, $reset);
	}
}
?>