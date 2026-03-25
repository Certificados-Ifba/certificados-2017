<?php
namespace Application\Form;

use Application\Form\Filter\IdentificarParticipanteFilter;
use Base\Form\FormAbstract;
use Zend\Captcha\Dumb;
use Zend\Form\Element;

class IdentificarParticipanteForm extends FormAbstract
{

    public function __construct()
    {
        parent::__construct('');
        
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'login-form');
        $this->setAttribute('id', 'form');

        $this->setInputFilter((new IdentificarParticipanteFilter())->getInputFilter());
        
        $this->add(array(
            'name' => 'cpf',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control placeholder-no-fix',
                'id' => 'cpf',
                'placeholder' => 'Informe seu CPF',
                'required' => true
            ),
            'options' => array(
                'label' => 'Informe seu CPF'
            )
        ));
        
        $this->add(array(
            'name' => 'data_nascimento',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control placeholder-no-fix',
                'id' => 'data_nascimento',
                'placeholder' => 'Informe sua data de nascimento',
                'required' => 'required'
            ),
            'options' => array(
                'label' => 'Informe sua data de nascimento'
            )
        ));
        
        $evento = new Element\Select('evento');
        $evento->setEmptyOption('Selecionar evento');
        
        $evento->setAttributes(array(
            'class' => 'form-control placeholder-no-fix',
            'id' => 'evento',
            'placeholder' => 'Evento',
            'required' => 'required'
        ));
        $evento->setOptions(array(
            'label' => 'Evento'
        ));
        
        $evento->setDisableInArrayValidator(true);
        $this->add($evento);

        //$recaptcha = new ZendService\ReCaptcha\ReCaptcha('6LfFeUYUAAAAAIDyKbMbhGxNGdvzw74Aa375qR7S', '6LfFeUYUAAAAAJKojud7UOaVuF2MK8ywV7DnH8gK');

        $captcha = new Element\Captcha('captcha');
        $captcha->setCaptcha(new \Zend\Captcha\ReCaptcha(array(
            'secret_key' => getenv('RECAPTCHA_SECRET_KEY') ?: '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
            'site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
        )));
        $this->add($captcha);

        $csrf = new Element\Csrf('csrf');
        $csrf->setOptions(array(
            'csrf_options' => array(
                'timeout' => 9000
            )
        ));
        $this->add($csrf);


        $submit = new Element\Submit('submit');
        $submit->setValue("Buscar certificados");
        $submit->setAttribute('class', 'btn green btn-block');
        $submit->setAttribute('id','btn-buscar-certificados');
        $submit->setAttribute('style', 'padding: 13px 0 !important;');

        $this->add($submit);
    }
}