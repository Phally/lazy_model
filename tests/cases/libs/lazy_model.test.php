<?php
App::import('Lib', 'LazyModel.LazyModel');
abstract class LazyAppModel extends LazyModel {
	public function getLazyMap() {
		return $this->map;
	}
}

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

class LazyModelTestCase extends CakeTestCase {
	public $fixtures = array('core.article', 'core.user', 'core.tag', 'core.articles_tag');
	
	public function testLazyLoadingNonHABTM() {
		$user = ClassRegistry::init('User');

		$this->assertFalse(property_exists($user, 'Article'));
		
		$user->Article->create();
		
		$this->assertTrue(property_exists($user, 'Article'));
	}
	
	public function testLazyLoadingHABTM() {
		$article = ClassRegistry::init('Article');
		
		$this->assertTrue(property_exists($article, 'Tag'));
		$this->assertTrue(property_exists($article, 'ArticlesTag'));
		$this->assertFalse(property_exists($article, 'User'));
		
		$article->User->create();
		
		$this->assertTrue(property_exists($article, 'Tag'));
		$this->assertTrue(property_exists($article, 'ArticlesTag'));
		$this->assertTrue(property_exists($article, 'User'));
	}

	public function testOptimizedLazyLoadingHABTM() {
		$article = ClassRegistry::init('HalfLazyLoadedArticle');

		$this->assertFalse(property_exists($article, 'HalfLazyLoadedTag'));
		$this->assertTrue(property_exists($article, 'HalfLazyLoadedArticlesTag'));
		$this->assertFalse(property_exists($article, 'HalfLazyLoadedUser'));

		$article->HalfLazyLoadedUser->create();

		$this->assertFalse(property_exists($article, 'HalfLazyLoadedTag'));
		$this->assertTrue(property_exists($article, 'HalfLazyLoadedArticlesTag'));
		$this->assertTrue(property_exists($article, 'HalfLazyLoadedUser'));
	}
	
	public function testNoRecursion() {
		$article = ClassRegistry::init('Article');
		$results = $article->find('all', array('recursive' => -1));
		
		$this->assertEqual(Set::extract('/Article/id', $results), array(1,2,3));
		$this->assertEqual(array_keys($results[0]), array('Article'));
	}
	
	public function testRecursionLevel0() {
		$article = ClassRegistry::init('Article');
		$results = $article->find('all', array('recursive' => 0));
		
		$this->assertEqual(Set::extract('/Article/id', $results), array(1,2,3));
		$this->assertEqual(Set::extract('/User/id', $results), array(1,3,1));
		$this->assertEqual(array_keys($results[0]), array('Article', 'User'));
	}
	
	public function testRecursionLevel1() {
		$article = ClassRegistry::init('Article');
		$results = $article->find('all', array('recursive' => 1));
		
		$this->assertEqual(Set::extract('/Article/id', $results), array(1,2,3));
		$this->assertEqual(Set::extract('/User/id', $results), array(1,3,1));
		$this->assertEqual(Set::extract('/0/Tag/id', $results), array(1,2));
		$this->assertEqual(array_keys($results[0]), array('Article', 'User', 'Tag'));
	}
	
	public function testContainable() {
		$user = ClassRegistry::init('User');
		$user->Behaviors->attach('Containable');
		
		$results = $user->find('all', array('contain' => array('Article' => array('limit' => 1))));
		
		$this->assertEqual(Set::extract('/Article/id', $results), array(1,2));
		$this->assertEqual(array_keys($results[0]), array('User', 'Article'));
	}

	public function testResetAssociations() {
		$article = ClassRegistry::init('Article');
		$article->Behaviors->attach('Containable');

		$this->assertFalse(property_exists($article, 'User'));
		$this->assertTrue(property_exists($article, 'Tag'));

		$article->find('first', array('contain' => array('Tag')));

		$this->assertFalse(property_exists($article, 'User'));
	}

	public function testMapEntries() {
		$article = ClassRegistry::init('Article');

		$result = $article->getLazyMap();
		$expected = array(
			'User' => 'User'
		);
		$this->assertIdentical($result, $expected);

		$result = $article->User->getLazyMap();
		$expected = array(
			'Article' => 'Article'
		);
		$this->assertIdentical($result, $expected);

		$result = $article->Tag->getLazyMap();
		$expected = array();
		$this->assertIdentical($result, $expected);
	}

	public function endTest() {
		ClassRegistry::flush();
	}
}
?>