<?php

namespace Kunstmaan\FormBundle\Entity\PageParts;

use ArrayObject;

use Doctrine\ORM\Mapping as ORM;

use Kunstmaan\FormBundle\Entity\FormSubmissionFieldTypes\TextFormSubmissionField;
use Kunstmaan\FormBundle\Form\TextFormSubmissionType;
use Kunstmaan\FormBundle\Form\MultiLineTextPagePartAdminType;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The multi-line text page part can be used to create forms with multi-line text fields
 *
 * @ORM\Entity
 * @ORM\Table(name="kuma_multi_line_text_page_parts")
 */
class MultiLineTextPagePart extends AbstractFormPagePart
{

    /**
     * If set to true, you are obligated to fill in this page part
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $required = false;

    /**
     * Error message shows when the page part is required and nothing is filled in
     *
     * @ORM\Column(type="string", name="error_message_required", nullable=true)
     */
    protected $errorMessageRequired;

    /**
     * If set the entered value will be matched with this regular expression
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $regex;

    /**
     * If a regular expression is set and it doesn't match with the given value, this error message will be shown
     *
     * @ORM\Column(type="string", name="error_message_regex", nullable=true)
     */
    protected $errorMessageRegex;

    /**
     * Set the regular expression to match the entered value against
     *
     * @param string $regex
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;
    }

    /**
     * Get the current regular expression
     *
     * @return string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Set the error message which will be shown when the entered value doesn't match the regular expression
     *
     * @param string $errorMessageRegex
     */
    public function setErrorMessageRegex($errorMessageRegex)
    {
        $this->errorMessageRegex = $errorMessageRegex;
    }

    /**
     * Get the current error message which will be shown when the entered value doesn't match the regular expression
     *
     * @return string
     */
    public function getErrorMessageRegex()
    {
        return $this->errorMessageRegex;
    }

    /**
     * Returns the frontend view
     *
     * @return string
     */
    public function getDefaultView()
    {
        return "KunstmaanFormBundle:MultiLineTextPagePart:view.html.twig";
    }

    /**
     * Sets the required valud of this page part
     *
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * Check if the page part is required
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Sets the message shown when the page part is required and no value was entered
     *
     * @param string $errorMessageRequired
     */
    public function setErrorMessageRequired($errorMessageRequired)
    {
        $this->errorMessageRequired = $errorMessageRequired;
    }

    /**
     * Get the error message that will be shown when the page part is required and no value was entered
     *
     * @return string
     */
    public function getErrorMessageRequired()
    {
        return $this->errorMessageRequired;
    }

    /**
     * Modify the form with the fields of the current page part
     *
     * @param FormBuilderInterface $formBuilder The form builder
     * @param ArrayObject          $fields      The fields
     */
    public function adaptForm(FormBuilderInterface $formBuilder, ArrayObject $fields)
    {
        $mfsf = new TextFormSubmissionField();
        $mfsf->setFieldName("field_" . $this->getUniqueId());
        $mfsf->setLabel($this->getLabel());
        $data = $formBuilder->getData();
        $data['formwidget_' . $this->getUniqueId()] = $mfsf;
        $label = $this->getLabel();
        $formBuilder->add('formwidget_' . $this->getUniqueId(), new TextFormSubmissionType($label));
        $formBuilder->setData($data);
        if ($this->getRequired()) {
            $thiss = $this;
            $formBuilder->addEventListener(FormEvents::POST_BIND, function(FormEvent $formEvent) use ($mfsf, $thiss) {
                $form = $formEvent->getForm();

                $value = $mfsf->getValue();
                if (is_null($value) || !is_string($value) || empty($value)) {
                    $errormsg = $thiss->getErrorMessageRequired();
                    $v = $form->get('formwidget_' . $thiss->getUniqueId())->get('value');
                    $v->addError(new FormError(empty($errormsg) ? AbstractFormPagePart::ERROR_REQUIRED_FIELD : $errormsg));
                }
            });
        }
        if ($this->getRegex()) {
            $thiss = $this;
            $formBuilder->addEventListener(FormEvents::POST_BIND, function(FormEvent $formEvent) use ($mfsf, $thiss) {
                $form = $formEvent->getForm();

                $value = $mfsf->getValue();
                if (!is_null($value) && is_string($value) && !preg_match('/' . $thiss->getRegex() . '/', $value)) {
                    $v = $form->get('formwidget_' . $thiss->getUniqueId())->get('value');
                    $v->addError(new FormError($thiss->getErrorMessageRegex()));
                }
            });
        }
        $fields[] = $mfsf;
    }

    /**
     * Returns the default backend form type for this page part
     *
     * @return MultiLineTextPagePartAdminType
     */
    public function getDefaultAdminType()
    {
        return new MultiLineTextPagePartAdminType();
    }

}
