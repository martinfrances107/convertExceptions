<?php
require_once 'vendor/autoload.php';
use Pharborist\Parser;
use Pharborist\Filter;
use Pharborist\WhitespaceNode;

function processFile($filename) {
  $tree = Parser::parseFile($filename);
  // Iterate over each doc block in the file.
  foreach ($tree->find(Filter::isInstanceOf('\Pharborist\DocCommentNode')) as $node) {
    /** @var \Pharborist\DocCommentNode $doc_block */
    $doc_block = $node->getDocBlock();
    $class_tags = $doc_block->getTagsByName('expectedException');
    if ($class_tags) {
      $exception_class = $class_tags[0]->getDescription();
      //echo "$exception_class\n";
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

      // Search method class - skip whitespaces and open brackets find the
      // first line of code.
      // Prepare new statement. ( code - newlines - indent ).
      $indentation = str_repeat(" ", $method_class->getColumnNumber() +  1);
      if ($message) {
        $statement = "\$this->setExpectedException('" . $exception_class . "'"  . $message . ");" . PHP_EOL . PHP_EOL. $indentation;
      }
      else {
        $statement = "\$this->setExpectedException('$exception_class');" . PHP_EOL . PHP_EOL . $indentation;
      }
      $new_nodes = Parser::parseSource($statement);
      // The next two lines of code are crappy -  it will not scale with large
      // method classes.
      $lines_of_code = $method_class->find(
        Filter::any([
          Filter::isInstanceOf('Pharborist\StatementNode'),
          Filter::isComment(FALSE) // Comment that is not a docBlock.
        ])
      );
      $first_line_of_code = $lines_of_code->first();

      $new_nodes->insertBefore($first_line_of_code);

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
//processFile('./test.php');

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
