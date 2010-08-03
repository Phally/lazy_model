<?php
abstract class LazyModel extends Model {
	public $actsAs = array('Containable');
	
	private $__userAssociations = array();
	private $__aliases = array();
	private $__joinClasses = array();
	
	public function __construct($id = false, $table = null, $ds = null) {
		
		foreach ($this->__associations as $type) {
			$this->__userAssociations[$type] = $this->{$type};
			$this->__aliases = array_merge($this->__aliases, array_keys($this->{$type}));
		}
		
		foreach (Set::extract('/with/.', array_values($this->hasAndBelongsToMany)) as $raw) {
			list($plugin, $alias) = pluginSplit($raw);
			$this->__joinClasses[$alias] = $raw;
		}
		
		parent::__construct($id, $table, $ds);
		
	}
	
	public function __constructLinkedModel() {
	}
	
	public function __isset($alias) {
		return $alias && in_array($alias, array_merge($this->__aliases, $this->__joinClasses));
	}
	
	public function __get($alias) {
		foreach ($this->__userAssociations as $type => $associations) {
			if (isset($associations[$alias]) || in_array($alias, array_keys($this->__joinClasses))) {
				$class = isset($associations[$alias]) ? $associations[$alias]['className'] : $this->__joinClasses[$alias];
				$this->__constructLazyLinkedModel($alias, $class);
				return ClassRegistry::getObject($alias);
			}
		}
	}
	
	private function __constructLazyLinkedModel($alias, $class = null) {
		$this->{$alias} = ClassRegistry::init(compact('class', 'alias'));
		if (strpos($class, '.') !== false) {
			ClassRegistry::addObject($class, $this->{$alias});
		}
		$this->tableToModel[$this->{$alias}->table] = $alias;
	}
	
	public function bindModel($models, $reset = true) {
		foreach ($models as $type => &$data) {
			foreach ($data as $alias => &$join) {
				if ($type == 'belongsTo' && isset($this->__userAssociations['hasMany'][$alias]['className'])) {
					$join['className'] = $this->__userAssociations['hasMany'][$alias]['className'];
				}
				$this->__userAssociations[$type][$alias] = $join;
				$this->__aliases[] = $alias;
			}
		}
		parent::bindModel($models, $reset);
	}
	
}
?>