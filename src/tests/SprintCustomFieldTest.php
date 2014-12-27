<?php
final class SprintCustomFieldTest extends SprintTestCase {

  public function testgetBeginFieldName()  {
    $subclassname = new SprintBeginDateField();
    $fieldname = $subclassname->getFieldName();
    $this->assertEquals($fieldname, 'Sprint Start Date');
  }

  public function testgetBeginFieldKey()  {
    $subclassname = new SprintBeginDateField();
    $fieldname = $subclassname->getFieldKey();
    $this->assertEquals($fieldname, 'isdc:sprint:startdate');
  }

  public function testgetBeginFieldDescription()  {
    $subclassname = new SprintBeginDateField();
    $fieldname = $subclassname->getFieldDescription();
    $this->assertEquals($fieldname, 'When a sprint starts');
  }

  public function testgetStoryPointsFieldName()  {
    $subclassname = new SprintTaskStoryPointsField();
    $fieldname = $subclassname->getFieldName();
    $this->assertEquals($fieldname, 'Story Points');
  }

  public function testgetStoryPointsFieldKey()  {
    $subclassname = new SprintTaskStoryPointsField();
    $fieldname = $subclassname->getFieldKey();
    $this->assertEquals($fieldname, 'isdc:sprint:storypoints');
  }

  public function testgetStoryPointsFieldDescription()  {
    $subclassname = new SprintTaskStoryPointsField();
    $fieldname = $subclassname->getFieldDescription();
    $this->assertEquals($fieldname, 'Estimated story points for this task');
  }

  public function testgetEndFieldName()  {
    $subclassname = new SprintEndDateField();
    $fieldname = $subclassname->getFieldName();
    $this->assertEquals($fieldname, 'Sprint End Date');
  }

  public function testgetEndFieldKey()  {
    $subclassname = new SprintEndDateField();
    $fieldname = $subclassname->getFieldKey();
    $this->assertEquals($fieldname, 'isdc:sprint:enddate');
  }

  public function testgetEndFieldDescription()  {
    $subclassname = new SprintEndDateField();
    $fieldname = $subclassname->getFieldDescription();
    $this->assertEquals($fieldname, 'When a sprint ends');
  }

    public function testgetDateFieldProxy() {
    $classname = 'SprintProjectCustomField';
    $datefield = new PhabricatorStandardCustomFieldDate();

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('getDateFieldProxy'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('getDateFieldProxy')
        ->with($datefield, $this->anything(), $this->anything())
        ->will($this->returnValue($datefield));

    $proxy = $mock->getDateFieldProxy($datefield, $this->anything(), $this->anything());
    $this->assertEquals('PhabricatorStandardCustomFieldDate', get_class($proxy));
  }

  public function testCustomFieldImplementationIncompleteException() {
    $this->setExpectedException('PhabricatorCustomFieldImplementationIncompleteException');
    $classname = 'PhabricatorCustomField';
    $datefield = new SprintBeginDateField();

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('getFieldKey'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('getFieldKey')
        ->will($this->throwException(new PhabricatorCustomFieldImplementationIncompleteException($datefield)));

    $mock->getFieldKey();
  }

  public function testnewDateControl()
  {
    $classname = 'SprintProjectCustomField';
    $datecontrol = new AphrontFormDateControl();
    $proxy = new PhabricatorStandardCustomFieldDate();

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('newDateControl'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('newDateControl')
        ->with($proxy, $this->anything())
        ->will($this->returnValue($datecontrol));

    $control = $mock->newDateControl($proxy, $this->anything());
    $this->assertEquals('AphrontFormDateControl', get_class($control));
  }

  public function testcheckForSprint()  {
    $validator = new SprintValidator();
    $mword = SprintConstants::MAGIC_WORD;
    $showfields = array($validator, 'shouldShowSprintFields');
    $project = id(new PhabricatorProject())->setName($mword.'Test');
    $is_sprint = $validator->checkForSprint($showfields, $project);
    $this->assertTrue($is_sprint);
  }

  public function testcheckForNonSprint()  {
    $validator = new SprintValidator();
    $showfields = array($validator, 'shouldShowSprintFields');
    $project = id(new PhabricatorProject())->setName('Test');
    $is_sprint = $validator->checkForSprint($showfields, $project);
    $this->assertFalse($is_sprint);
  }

  public function testrenderDateProxyPropertyViewValue()
  {
    $classname = 'SprintProjectCustomField';
    $datefield = new PhabricatorStandardCustomFieldDate();

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('renderDateProxyPropertyViewValue'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('renderDateProxyPropertyViewValue')
        ->with($datefield, $this->anything())
        ->will($this->returnValue($datefield));

    $proxy = $mock->renderDateProxyPropertyViewValue($datefield, $this->anything());
    $this->assertEquals('PhabricatorStandardCustomFieldDate', get_class($proxy));
  }

  public function testrenderDateProxyEditControl()
  {
    $classname = 'SprintProjectCustomField';
    $datefield = new PhabricatorStandardCustomFieldDate();

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('renderDateProxyEditControl'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('renderDateProxyEditControl')
        ->with($datefield, $this->anything())
        ->will($this->returnValue($datefield));

    $proxy = $mock->renderDateProxyEditControl($datefield, $this->anything());
    $this->assertEquals('PhabricatorStandardCustomFieldDate', get_class($proxy));
  }

  public function testrenderPropertyViewValue()
  {
    $classname = 'SprintProjectCustomField';
    $proxy =  new PhabricatorStandardCustomFieldDate();
    $handles = array();

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('renderPropertyViewValue'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('renderPropertyViewValue')
        ->with($handles)
        ->will($this->returnValue($proxy));

    $this->assertSame($proxy, $mock->renderPropertyViewValue($handles));
  }

  public function testgetStandardCustomFieldNamespace()
  {
    $classname = 'SprintProjectCustomField';

    $mock = $this->getMockBuilder($classname)
        ->disableOriginalConstructor()
        ->setMethods(array('getStandardCustomFieldNamespace'))
        ->getMockforAbstractClass();

    $mock->expects($this->once())
        ->method('getStandardCustomFieldNamespace')
        ->will($this->returnValue('project'));

    $value = $mock->getStandardCustomFieldNamespace();
    $this->assertEquals('project', $value);
  }

}