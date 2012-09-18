<?php

namespace Kunstmaan\FormBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;

use Kunstmaan\FormBundle\Form\AbstractFormPageAdminType;
use Kunstmaan\FormBundle\Entity\FormSubmission;
use Kunstmaan\FormBundle\Entity\FormSubmissionField;
use Kunstmaan\AdminNodeBundle\Entity\AbstractPage;
use Kunstmaan\AdminNodeBundle\Entity\NodeTranslation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The Abstract ORM FormPage
 */
abstract class AbstractFormPage extends AbstractPage
{
    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="text", nullable=true)
     */
    protected $thanks;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $subject;

    /**
     * @ORM\Column(type="string", name="from_email", nullable=true)
     * @Assert\Email()
     */
    protected $fromEmail;

    /**
     * @ORM\Column(type="string", name="to_email", nullable=true)
     */
    protected $toEmail;

    /**
     * @param string $thanks
     */
    public function setThanks($thanks)
    {
        $this->thanks = $thanks;
    }

    /**
     * @return string
     */
    public function getThanks()
    {
        return $this->thanks;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getToEmail()
    {
        return $this->toEmail;
    }

    /**
     * @param string $toEmail
     */
    public function setToEmail($toEmail)
    {
        $this->toEmail = $toEmail;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    /**
     * @param ContainerInterface $container The Container
     * @param Request            $request   The Request
     * @param array              &$result   The Result array
     *
     * @return RedirectResponse|null
     */
    public function service(ContainerInterface $container, Request $request, &$result)
    {
        $thanksParam = $request->get('thanks');
        if (!empty($thanksParam)) {
            $result["thanks"] = true;
        } else {
            /* @var $formbuilder FormBuilderInterface */
            $formbuilder = $container->get('form.factory')->createBuilder('form');
            /* @var $em EntityManager */
            $em = $container->get('doctrine')->getEntityManager();
            /* @var $fields FormSubmissionField[] */
            $fields = array();
            $pageparts = $em->getRepository('KunstmaanPagePartBundle:PagePartRef')->getPageParts($this, $this->getFormElementsContext());
            foreach ($pageparts as $pagepart) {
                if ($pagepart instanceof FormAdaptorInterface) {
                    $pagepart->adaptForm($formbuilder, $fields);
                }
            }
            $form = $formbuilder->getForm();
            if ($request->getMethod() == 'POST') {
                $form->bind($request);
                if ($form->isValid()) {
                    $formSubmission = new FormSubmission();
                    $formSubmission->setIpAddress($request->getClientIp());
                    $formSubmission->setNode($em->getRepository('KunstmaanAdminNodeBundle:Node')->getNodeFor($this));
                    $formSubmission->setLang($locale = $request->getLocale());
                    $em->persist($formSubmission);
                    foreach ($fields as $field) {
                        $field->setSubmission($formSubmission);
                        $field->onValidPost($form, $formbuilder, $request, $container);
                        $em->persist($field);
                    }
                    $em->flush();
                    $em->refresh($formSubmission);

                    $from = $this->getFromEmail();
                    $to = $this->getToEmail();
                    $subject = $this->getSubject();
                    if (!empty($from) && !empty($to) && !empty($subject)) {
                        $container->get('form.mailer')->sendContactMail($formSubmission, $from, $to, $subject);
                    }

                    /* @var $nodeTranslation NodeTranslation */
                    $nodeTranslation = $result['nodetranslation'];

                    return new RedirectResponse($container->get('router')->generate('_slug', array(
                        'url' => $result['slug'],
                        '_locale' => $nodeTranslation->getLang(),
                        'thanks' => true
                    )));
                }
            }
            $result["frontendform"] = $form->createView();
            $result["frontendformobject"] = $form;
        }

        return null;
    }

    /**
     * @return array
     */
    abstract public function getPagePartAdminConfigurations();

    /**
     * @return string
     */
    abstract public function getDefaultView();

    /**
     * @return AbstractFormPageAdminType
     */
    public function getDefaultAdminType()
    {
        return new AbstractFormPageAdminType();
    }

    /**
     * @return string
     */
    public function getFormElementsContext()
    {
        return "main";
    }

}
