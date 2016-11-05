<?php

class LibraryDiscoveryParserTest {

  /**
   * Tests handling of expression node.
   *
   * @expectedException \ExpressionNodeClass
   * @expectedExceptionMessage 'Test expression node'
   *
   * @covers ::bar
   */
  public function testExpressionNode() {
    $this->foo();
    echo 'hello world';
    Foo::bar();
  }

  /**
   * Tests handling of a echo statement.
   *
   * @expectedException \EchoFirstClass
   * @expectedExceptionMessage "Test echo statment node"
   *
   * @covers ::world
   */
  public function testLibraryThirdPartyWithMissingLicense() {
    echo 'hello world';
    $hello->world();
  }

  /**
   *
   * @expectedException \Commentfirst
   * @expectedExceptionMessage 'Tests code is inserted'
   *
   * @covers ::foo
   */
  public function testBeforeComment() {
    /* expect insertion before here */
    $this->foo();
    echo 'hello world';
  }

}
