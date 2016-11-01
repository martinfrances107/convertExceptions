<?php

require_once 'vendor/autoload.php';

use Pharborist\Parser;
use Pharborist\Filter;

function processFile($filename) {
  $tree = Parser::parseFile($filename);

  // Iterate over each doc block in the file.
  foreach ($tree->find(Filter::isInstanceOf('\Pharborist\DocCommentNode')) as $node) {
    /** @var \Pharborist\DocCommentNode $node */
    $doc_block = $node->getDocBlock();

    $class_tags = $doc_block->getTagsByName('expectedException');
    if ($class_tags) {
      $exception_class = $class_tags[0]->getDescription();
      echo "$exception_class\n";

      $message_tags = $doc_block->getTagsByName('expectedExceptionMessage');
      if ($message_tags) {
        $message = $message_tags[0]->getDescription();
        echo "$message\n";
      }

      $method_class = $node->parent(Filter::isInstanceOf('Pharborist\Objects\ClassMethodNode'));

      // todo
      // use $exception_class and $message to
      // insert $this->setExpectedException('') into $method_class
      var_dump($method_class);

    }
  }
}

processFile('./test.php');

// This exposed a flaw in pharborist.
// Unless Drupal core is patched with  DO-NOT_COMMIT-2822837-7.patch the classes
// in core/modules/system/src/Tests/Installer will cause errors.



// The following code will iterate over every class in D8 core
// - please /Users/martin/sites/drupal/core to point to the core directory for your system.
  /*
  $directory = new \RecursiveDirectoryIterator('/Users/martin/sites/drupal/core');
  $iterator = new \RecursiveIteratorIterator($directory);
  $pattern = '/^.+Test\.php$/i';
  $regex = new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);
  foreach ($regex as $name => $object) {
    echo "processing: $name\n";
    processFile($name);
  }
  */
