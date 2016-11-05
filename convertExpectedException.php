<?php
require_once 'vendor/autoload.php';
use Pharborist\Parser;
use Pharborist\Filter;

function processFile($filename) {
  $tree = Parser::parseFile($filename);
  // Iterate over each doc block in the file.
  foreach ($tree->find(Filter::isInstanceOf('\Pharborist\DocCommentNode')) as $node) {
    /** @var \Pharborist\DocCommentNode $doc_block */
    $doc_block = $node->getDocBlock();
    $class_tags = $doc_block->getTagsByName('expectedException');
    if ($class_tags) {
      $exception_class = $class_tags[0]->getDescription();
      $message_tags = $doc_block->getTagsByName('expectedExceptionMessage');
      // Handle multiple combinations of delimiters.
      if ($message_tags) {
        $message = $message_tags[0]->getDescription();
        if(strpos($message, "'") !== FALSE) {
          if(strpos($message, '"') !== FALSE) {
            $message = preg_replace('/"/', '\\"', $message);
          }
          $message = ', "' . $message . '"';
        }
        else{
          $message = ", '" . $message . "'";
        }
      }
      else {
        $message = '';
      }

      $method_class = $node->parent(Filter::isInstanceOf('Pharborist\Objects\ClassMethodNode'));

      // Look for a covers tag.
      $call_found = FALSE;
      $covers_tags = $doc_block->getTagsByName('covers');
      if(count($covers_tags) == 1 ) {
        $method_name = preg_replace('/^::/', '', $covers_tags[0]->getReference());
        echo 'look for -', $method_name, "\n";

        // Look for a single method call.
        // calls have 2 possible types:
        // Foo::bar()
        // $foo->bar();
        $method_calls = $method_class->find(
          Filter::any([
            Filter::isInstanceOf('Pharborist\Objects\ObjectMethodCallNode'),
            Filter::isInstanceOf('Pharborist\Objects\ClassMethodCallNode'),
          ])
        );

        // Loop over the method calls looking for the method calls under test.
        $call_count = 0;
        foreach($method_calls as $method_call) {
          if ($method_call->getMethodName()->getText() == $method_name) {
            $call_count++;
          }
        }
        // Only take action if there is one method call.
        $call_found = ($call_count == 1);
      }

      // statement =  code + tail.
      //
      // Prepare tail.
      $indentation = str_repeat(" ", $method_class->getColumnNumber() +  1);
      if ($call_found) {
        $tail = PHP_EOL . $indentation;
      }
      else {
        // leave blank line after new code.
        $tail = PHP_EOL . PHP_EOL . $indentation;
      }

      // Construct set of nodes to be inserted.
      if ($message) {
        $statement = "\$this->setExpectedException('" . $exception_class . "'" . $message . ");" . $tail;
      }
      else {
        $statement = "\$this->setExpectedException('$exception_class');" . $tail;
      }
      $new_nodes = Parser::parseSource($statement);

      // If direct call to method in @covers tags is found insert new code
      // directly above.
      if ($call_found) {
        $new_nodes->insertBefore($method_call);
      }
      else {
        // Fallback to the start.
        //
        // Search method class - skip whitespaces and open brackets find the
        // first line of code ( or comment block ).
        //
        // The next two lines of code are crappy -  it will not scale with large
        // method classes.
        $lines_of_code = $method_class->find(
          Filter::any([
            Filter::isInstanceOf('Pharborist\StatementNode'),
            Filter::isComment(FALSE) // Comment that is not a docBlock.
          ])
        );
        $first_line = $lines_of_code->first();

        $new_nodes->insertBefore($first_line);
      }

      // Delete the tags from the doc block.
      /** @var Pharborist\DocCommentNode $doc_comment */
      $doc_comment = $method_class->getDocComment();
      $text = $doc_comment->getText();
      $text = preg_replace('/\n\s*\* \@expectedException.+?\n/', "\n", $text);
      $text = preg_replace('/\n\s*\* \@expectedExceptionMessage.+?\n/', "\n", $text);
      $text = preg_replace('/\n\s*\*\s*\n(\s*\*\/)/', "\n$1", $text);
      $doc_comment->setText($text);
      $method_class->setDocComment($doc_comment);

      // Phsrborist add a newline that we need to remove.
      $unfiltered_result = $tree->getText();
      $result = preg_replace('@\*/\n  (?=\n)@', '*/', $unfiltered_result);

      file_put_contents($filename, $result);
    }
  }
}

processFile('./test.php');

function recursive($dir)
{
  global $directories;

  $odir = opendir($dir);

  while (($file = readdir($odir)) !== FALSE)
  {
    if ($file != '.' && $file != '..' && is_dir($dir.DIRECTORY_SEPARATOR.$file))
    {
      if($file == "Tests" || $file == "tests"){
        $directories[] = $dir.DIRECTORY_SEPARATOR.$file;
      }
      else {
        recursive($dir.DIRECTORY_SEPARATOR.$file);
      }
    }
  }
  closedir($odir);
}

$dir = '/path/to/drupal.site/core';
$directory = new \RecursiveDirectoryIterator($dir);
$directories = [];
recursive($dir);
foreach($directories as $dir){
  $directory = new \RecursiveDirectoryIterator($dir);
  $iterator = new \RecursiveIteratorIterator($directory);
  $pattern = '/^.+Test\.php$/i';
  $regex = new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);
  foreach ($regex as $name => $object) {
    echo "processing: $name\n";
    processFile($name);
  }
}
