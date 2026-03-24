<?php
namespace Application\Form;

use Base\Form\FormAbstract;
use Zend\Form\Element;
use Zend\Form\Element\Csrf;

class ValidarCertificado extends FormAbstract
{

    public function __construct($_name = 'Salvar')
    {
        parent::__construct($_name);
        $this->setAttribute('enctype', "multipart/form-data");
        
        $this->setInputFilter((new Filter\ValidarCertificado())->getInputFilter());
        
        $chave = new Element\Text('chave');
        $chave->setLabel('Chave')->setAttributes(array(
            'placeholder' => 'Informe aqui o código de registro do certificado',
            'class' => 'form-control',
            'required' => 'required',
            'id' => 'chave'
        ));
        $this->add($chave);

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
        

        
        $submit = new Element\Submit('submit');
        $this->add($submit);
    }

    public function editingMode()
    {}
}