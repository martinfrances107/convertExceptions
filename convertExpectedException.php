<?php

require_once 'vendor/autoload.php';

use Pharborist\Parser;
use Pharborist\Filter;

$tree = Parser::parseFile('test.php');

$comments = Filter::isInstanceOf('\Pharborist\CommentNode');

foreach( $tree->find(Filter::isInstanceOf('\Pharborist\DocCommentNode')) as $node ) {
  /** @var \Pharborist\DocCommentNode $node */
  $tag = $node->getDocBlock()->getTagsByName('expectedException');
  foreach($tag->getDescription() as $desc) {
    var_dump($desc);
}
}

