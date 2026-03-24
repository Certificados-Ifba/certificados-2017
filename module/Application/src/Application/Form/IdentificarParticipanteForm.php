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

        $appEnv = getenv('APP_ENV') ?: 'production';
        $isDevelopment = in_array(strtolower($appEnv), array('dev', 'development', 'local'), true);

        // Chaves de teste oficiais do Google (válidas para localhost/desenvolvimento)
        $defaultSiteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
        $defaultSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

        $siteKey = getenv('RECAPTCHA_SITE_KEY') ?: $defaultSiteKey;
        $secretKey = getenv('RECAPTCHA_SECRET_KEY') ?: $defaultSecretKey;

        if (! $isDevelopment && ! getenv('RECAPTCHA_SITE_KEY')) {
            // Mantém compatibilidade com produção legada quando variável não existir
            $siteKey = '6LfFeUYUAAAAAIDyKbMbhGxNGdvzw74Aa375qR7S';
        }
        if (! $isDevelopment && ! getenv('RECAPTCHA_SECRET_KEY')) {
            $secretKey = '6LfFeUYUAAAAAJKojud7UOaVuF2MK8ywV7DnH8gK';
        }

        $captcha->setCaptcha(new \Zend\Captcha\ReCaptcha(array(
            'secret_key' => $secretKey,
            'site_key' => $siteKey,
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