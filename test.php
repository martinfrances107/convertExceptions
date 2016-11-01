<?php

class LibraryDiscoveryParserTest {

/**
   * Tests that an exception is thrown when license is missing when 3rd party.
   *
   * @expectedException \Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException
   * @expectedExceptionMessage Missing license information in library definition for definition 'no-license-info-but-remote' extension 'licenses_missing_information': it has a remote, but no license.
   *
   * @covers ::buildByExtension
   */
  public function testLibraryThirdPartyWithMissingLicense() {
  }

 }
