<?php
/**
 * Created by PhpStorm.
 * User: july
 * Date: 3/6/2015
 * Time: 3:35 AM
 */

namespace Application\Helper;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;


class ConstantHelper {
    protected $dbAdapter;
    public function __construct($dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    }
    protected $form;
    public function getForm()
    {
        if(!$this->form) {
            $hidId = new Element\Hidden();
            $hidId->setName('constantId');

            $txtName = new Element\Text();
            $txtName->setLabel('Constant Name')
                ->setName("name")
                ->setAttribute('class', 'form-control');

            $txtValue = new Element\Text();
            $txtValue->setLabel('Value')
                ->setName("value")
                ->setAttribute('class', 'form-control');

            $txtGroupCode = new Element\Text();
            $txtGroupCode->setLabel('Group Code')
                ->setName("group_code")
                ->setAttribute('class', 'form-control');


            $form = new Form();
            $form->setAttribute('class', 'form-horizontal');
            $form->add($hidId);
            $form->add($txtName);
            $form->add($txtValue);
            $form->add($txtGroupCode);

            $this->form = $form;
        }

        return $this->form;
    }

    public function setForm($form)
    {
        $this->form = $form;
    }

    protected $inputFilter;
    public function getInputFilter($constantId)
    {
        if(!$this->inputFilter) {
            $filter = new InputFilter();
            $filter->add(array(
                'name' => 'constantId',
                'required' => true,
                'filters' => array(
                    array('name' => 'Int'),
                )
            ));
            $filter->add(array(
                'name' => 'name',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'max' => 255,
                            'min' => 1,
                            'encoding' => 'UTF-8',
                        ),
                    ),
                    array(
                        'name' => 'Db\NoRecordExists',
                        'options' => array(
                            'table' => 'tbl_constant',
                            'field' => 'name',
                            'adapter' => $this->dbAdapter,
                            'exclude' => array(
                                'field' => 'constantId',
                                'value' => $constantId
                            ),
                            'message' => 'This constant  name is already exist.',
                        )
                    ),
                ),
            ));
            $filter->add(array(
                'name' => 'value',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'max' => 200
                        ),
                    ),
                ),
            ));
            $filter->add(array(
                'name' => 'group_code',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'max' => 200
                        ),
                    ),
                ),
            ));

            $this->inputFilter = $filter;
        }
        return $this->inputFilter;
    }

    public function setInputFilter($filter)
    {
        $this->inputFilter = $filter;
    }
}