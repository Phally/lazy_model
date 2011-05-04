<?php
/**
 * Abstract test models.
 */
abstract class LazyAppModel extends LazyModel {
	public function getLazyMap() {
		return $this->map;
	}
}

abstract class PreImplementation extends LazyAppModel {
	public $aliasOnConstructor = '';
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->aliasOnConstructor = $this->alias;
	}
	public function getStuff() {
		return true;
	}
}

abstract class AliasScrewUp extends PreImplementation {
	public $alias = 'InheritedAliasRapist';
}

/**
 * Normal lazy loading.
 */
class Article extends LazyAppModel {
	public $belongsTo = array('User');
	public $hasAndBelongsToMany = array('Tag');
}

class User extends LazyAppModel {
	public $hasMany = array('Article');
}

class Tag extends LazyAppModel {
	public $hasAndBelongsToMany = array('Article');
}

/**
 * HABTM optimized lazy loading.
 */
class HalfLazyLoadedArticle extends LazyAppModel {
	public $useTable = 'articles';
	public $belongsTo = array('HalfLazyLoadedUser');
	public $hasAndBelongsToMany = array(
		'HalfLazyLoadedTag' => array(
			'className' => 'HalfLazyLoadedTag',
			'with' => 'HalfLazyLoadedArticlesTag',
			'joinTable' => 'articles_tags',
			'associationForeignKey' => 'tag_id'
		)
	);
}

class HalfLazyLoadedUser extends LazyAppModel {
	public $useTable = 'users';
	public $hasMany = array('HalfLazyLoadedArticle');
}

class HalfLazyLoadedTag extends LazyAppModel {
	public $useTable = 'Tags';
	public $hasAndBelongsToMany = array(
		'HalfLazyLoadedArticle' => array(
			'className' => 'HalfLazyLoadedArticle',
			'with' => 'HalfLazyLoadedArticlesTag',
			'joinTable' => 'articles_tags',
			'associationForeignKey' => 'article_id'
		)
	);
}

class HalfLazyLoadedArticlesTag extends LazyAppModel {
	public $useTable = 'articles_tags';
	public $belongsTo = array('HalfLazyLoadedArticle', 'HalfLazyLoadedTag');
}

/**
 * Inheritance.
 */
class InheritedArticle extends PreImplementation {
	public $useTable = 'articles';
	public $belongsTo = array('InheritedUser');
}

class InheritedUser extends PreImplementation {
	public $useTable = 'users';
	public $hasMany = array('InheritedArticle');
}

class InheritedTag extends AliasScrewUp {
	public $useTable = 'tags';
}
?>
