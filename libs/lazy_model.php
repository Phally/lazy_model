<?php
abstract class LazyModel extends Model {
	private $map = array();
	private $auto = array();
	
	public function __construct($id = false, $table = null, $ds = null) {
		foreach ($this->__associations as $type) {
			foreach ((array)$this->{$type} as $key => $properties) {
				$this->map(false, $type, $key, $properties, $id);
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
			return $this->{$alias};
		}
	}
	
	private function constructLazyLinkedModel($alias, $class = null) {
		$this->{$alias} = ClassRegistry::init(compact('class', 'alias'));
		if (in_array($alias, $this->auto)) {
			ClassRegistry::removeObject($alias);
		} else {
			if (strpos($class, '.') !== false) {
				ClassRegistry::addObject($class, $this->{$alias});
			}
		}
		$this->tableToModel[$this->{$alias}->table] = $alias;
	}
	
	public function bindModel($models, $reset = true) {
		foreach ($models as $type => &$data) {
			foreach ($data as $key => &$properties) {
				$this->map(true, $type, $key, $properties);
			}
		}
		return parent::bindModel($models, $reset);
	}
	
	private function map($force, $type, $key, $properties, $id = null) {
		if (is_numeric($key)) {
			list($plugin, $alias) = pluginSplit($properties);
			$properties = array('className' => $properties);
		} else {
			$alias = $key;
			if (!isset($properties['className'])) {
				$properties['className'] = $alias;
			}
		}
		
		$this->addToMap($alias, $properties['className'], $force);
		
		if ($type == 'hasAndBelongsToMany') {
			if (isset($properties['with'])) {
				if (is_array($properties['with'])) {
					$properties['with'] = key($properties['with']);
				}
				list($plugin, $alias) = pluginSplit($properties['with']);
				$this->addToMap($alias, $properties['with'], $force);
			} else {
				if (isset($properties['joinTable'])) {
					$alias = Inflector::classify($properties['joinTable']);
					$this->addToMap($alias, $alias, $force);
				} else {
					$current = (is_array($id) && isset($id['alias'])) ? $id['alias'] : get_class($this);
					$aliases = array($alias, $current);
					sort($aliases);
					$alias = Inflector::pluralize($aliases[0]) . $aliases[1];
					$this->addToMap($alias, $alias, $force);
				}
				$this->auto[] = $alias;
				if (count($this->{$alias}->schema()) <= 2 && $this->{$alias}->primaryKey !== false) {
					if ($this->useTable) {
						$table = $this->useTable;
					} else {
						$table = Inflector::tableize(get_class($this));
					}
					$this->{$alias}->primaryKey = Inflector::singularize($table) . '_id';
				}
			}
		}
	}
	
	private function addToMap($alias, $class, $force) {
		if (!isset($this->map[$alias])) {
			$this->map[$alias] = $class;
		} elseif($force) {
			$this->map[$alias] = $class;
			$this->constructLazyLinkedModel($alias, $class);
		}
	}
}
?>