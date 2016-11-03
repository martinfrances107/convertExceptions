<?php

class LibraryDiscoveryParserTest {

/**
   * Tests handling of expression node.
   *
   * @expectedException \ExpressionNodeClass
   * @expectedExceptionMessage 'Test expression node'
   *
   * @covers ::buildByExtension
   */
  public function testExpressionNode() {
    $this->foo();
    echo 'hello world';
  }

  /**
   * Tests handling of a echo statement.
   *
   * @expectedException \EchoFirstClass
   * @expectedExceptionMessage "Test echo statment node"
   *
   * @covers ::buildByExtension
   */
  public function testLibraryThirdPartyWithMissingLicense() {
    echo 'hello world';
    $this->foo();
  }

  /**
   *
   * @expectedException \Commentfirst
   * @expectedExceptionMessage 'Tests code is inserted'
   *
   * @covers ::buildByExtension
   */
  public function testBeforeComment() {
    /* expect insertion before here */
    $this->foo();
    echo 'hello world';

  }

 }
