<?php
/**
 * Created by PhpStorm.
 * User: linn
 * Date: 3/6/2015
 * Time: 1:15 PM
 */

namespace HumanResource\Helper;


use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;

class DepartmentHelper {

    protected $dbAdapter;
    public function __construct($dbAdapter){
        $this->dbAdapter = $dbAdapter;
    }

    private $form;
    public function getForm()
    {
        if($this->form){
            return $this->form;
        }

        $form = new Form();
        $form->setAttribute('class', 'form-horizontal');
        $form->setAttribute('hr_department','form');
        $form->add(array(
            'name' => 'departmentId',
            'type' => 'Hidden',
        ));

        $form->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Title',
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        $form->add(array(
            'name' => 'description',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Description',
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        $form->add(array(
            'name' => 'group_code',
            'type' => 'Text',
            'options' => array(
                'label' => 'Group Code',
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        $form->add(array(
            'name' => 'parentId',
            'type' => 'number',
            'options' => array(
                'label' => 'Parent Id',
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        $form->add(array(
            'name' => 'priority',
            'type' => 'number',
            'options' => array(
                'label' => 'Priority',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'placeholder' => 'Choose priority',
            ),
        ));
        $form->add(array(
            'name' => 'status',
            'type' => 'text',
        ));

        $this->form = $form;

        return $this->form;
    }
    public function setForm($form)
    {
        $this->form = $form;
    }

    private $inputFilter;
    public function getInputFilter()
    {
        if($this->inputFilter){
            return $this->inputFilter;
        }

        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'departmentId',
            'required' => true,
            'filters' => array(
                array('name' => 'Int'),
            ),
        ));

        $filter->add(array(
            'name' => 'parentId',
            'required' => false,
        ));
        $filter->add(array(
            'name' => 'name',
            'required' => true,
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 255
                    ),
                ),
            ),
        ));

        $filter->add(array(
            'name' => 'description',
            'required' => false,
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
        ));
        $this->inputFilter = $filter;

        return $this->inputFilter;
    }
    public function setInputFilter($filter)
    {
        $this->inputFilter = $filter;
    }
}