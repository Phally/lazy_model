<?php
abstract class LazyModel extends Model {
	private $map = array();
	
	public function __construct($id = false, $table = null, $ds = null) {
		foreach ($this->__associations as $type) {
			foreach ($this->{$type} as $key => $properties) {
				$this->map($type, $key, $properties, $id);
			}
		}
		parent::__construct($id, $table, $ds);
	}

	public function __constructLinkedModel() {
	}

	public function __isset($alias) {
		return array_key_exists($alias, $this->map);
	}
	
	public function __get($alias) {
		if (isset($this->map[$alias])) {
			$this->constructLazyLinkedModel($alias, $this->map[$alias]);
		}
		return $this->{$alias};
	}
	
	private function constructLazyLinkedModel($alias, $class = null) {
		$this->{$alias} = ClassRegistry::init(compact('class', 'alias'));
		if (strpos($class, '.') !== false) {
			ClassRegistry::addObject($class, $this->{$alias});
		}
		$this->tableToModel[$this->{$alias}->table] = $alias;
	}
	
	public function bindModel($models, $reset = true) {
		foreach ($models as $type => &$data) {
			foreach ($data as $key => &$properties) {
				$this->map($type, $key, $properties);
			}
		}
		parent::bindModel($models, $reset);
	}
	
	private function map($type, $key, $properties, $id = null) {
		if (is_numeric($key)) {
			list($plugin, $alias) = pluginSplit($properties);
			$properties = array('className' => $properties);
		} else {
			$alias = $key;
		}
		
		$this->addToMap($alias, $properties['className']);
		
		if ($type == 'hasAndBelongsToMany') {
			if (isset($properties['with'])) {
				list($plugin, $alias) = pluginSplit($properties['with']);
				$this->addToMap($alias, $properties['with']);
			} else {
				$current = (is_array($id) && isset($id['alias'])) ? $id['alias'] : get_class($this);
				$aliases = array($alias, $current);
				sort($aliases);
				$alias = Inflector::pluralize($aliases[0]) . $aliases[1];
				$this->addToMap($alias, $alias);
			}
		}
	}
	
	private function addToMap($alias, $class) {
		if (!isset($this->map[$alias])) {
			$this->map[$alias] = $class;
		}
	}
}
?>