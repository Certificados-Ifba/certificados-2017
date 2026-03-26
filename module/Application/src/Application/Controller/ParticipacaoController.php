<?php
namespace Application\Controller;

use Application\Service\Certificado;
use Application\Service\Mail;
use Application\Service\Participacao;
use Base\Controller\ActionController;
use Dompdf\Dompdf;
use Dompdf\Options;
use Zend\Mvc\Controller\Plugin\FlashMessenger;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ParticipacaoController extends ActionController
{

    public function __construct()
    {
        $this->entity = 'Application\Entity\Participacao';
        $this->form = 'Application\Form\Participacao';
        $this->formService = false;
        $this->service = 'Application\Service\Participacao';
        $this->controller = 'participacao';
        $this->route = 'participacao/default';
    }

    public function cadastrarAction()
    {
        $evento = $this->getEm()
            ->getRepository('Application\Entity\Evento')
            ->find($this->params('evento'));
        
        if (is_null($evento)) {
            return $this->redirectHome();
        }
        
        if (! $this->podeAcessarParticipacao($evento)) {
            $this->flashMessenger()
                ->setNamespace('error')
                ->addMessage(' Você não é o coordenador desse evento e não pode visualisar ou alterar informações com essa conta ');
            
            return $this->redirect()->toRoute('coordenador/default', array(
                'action' => 'listar-eventos'
            ));
        }
        
        $todos_participantes_data = $this->getEm()
            ->getRepository('Application\Entity\Participacao')
            ->getTodosOsParticipantes($evento->getId());

        $participantes_data = $this->getEm()
            ->getRepository('Application\Entity\Participacao')
            ->getParticipantes($evento);
        
        $form = new \Application\Form\Participacao(null, $this->getEm(), $evento);
        
        return new ViewModel(array(
            'todos_participantes_data' => $todos_participantes_data,
            'participantes_data' => $participantes_data,
            'evento' => $evento,
            'form' => $form
        ));
    }

    /**
     *
     * @return JsonModel dados da participacao adicionada
     */
    public function addParticipanteAction()
    {

        $data       = $this->params()->fromPost();

        if(empty($data['qtd_bolsista']) || !isset($data['qtd_bolsista'])){
            unset($data['qtd_bolsista']);
        }

        $result     = (new Participacao($this->getEm()))->insert($data);

        $jsonModel = new JsonModel();

        if(count($result) == 0){

            $participante = $this->getEm()->getRepository('Application\Entity\Participante')->find($data['participante_id']);
            $atividade = $this->getEm()->getRepository('Application\Entity\Atividade')->find($data['atividade_id']);

            $jsonModel->setVariable('data', array());
            $jsonModel->setVariable('status', 412);
            $jsonModel->setVariable('message', $participante. ' já tem uma participação em '. $atividade. ' e por isso não foi inserido');

            return $jsonModel;
        }

        $jsonModel->setVariable('data', $result);
        $jsonModel->setVariable('status', 201);
        return $jsonModel;
    }

    public function removeParticipacaoAction()
    {

        $id =  $this->params()->fromPost('participacao_id', 0);

        /** @var  $participacao \Application\Entity\Participacao*/
        $participacao = $this->getEm()
            ->getRepository($this->entity)
            ->findOneBy(array(
            'id' => $id
        ));
        $participante_id = $participacao->getParticipante()->getId();
        $participante = $this->getEm()
            ->getRepository($this->entity)
            ->getParticipante($participante_id);
        $this->getEm()->remove($participacao);
        $this->getEm()->flush();

        $array = array_merge($participante, array(
            'atividade_titulo' => $participacao->getAtividade()->getTitulo()
        )) ;

        return new JsonModel($array);
    }

    public function getParticipantesAction()
    {
        $evento = $this->getEm()
            ->getRepository('Application\Entity\Evento')
            ->find($this->params('evento_id'));
        
        $data = $this->getEm()
            ->getRepository($this->entity)
            ->getParticipacoes($evento);
        
        return new JsonModel($data);
    }

    public function getNaoParticipantesAction()
    {
        $evento = $this->getEm()
            ->getRepository('Application\Entity\Evento')
            ->find($this->params('evento_id'));
        
        $data = $this->getEm()
            ->getRepository($this->entity)
            ->getNaoParticipantes($evento);
        
        return new JsonModel($data);
    }

    public function podeAcessarParticipacao(\Application\Entity\Evento $evento)
    {
        return $this->getTipoUsuarioLogado() == 'Administrador' || $evento->getUsuario()->getId() == $this->getUsuarioLogado()['id'];
    }

    public function enviarCertificadosPorEmailAction(){

        $evento = $this->getEm()
            ->getRepository('Application\Entity\Evento')
            ->find($this->params('evento'));

        $participacoes = $this->getEm()->getRepository('Application\Entity\Participacao')->getParticipacoes($evento);


        return new ViewModel(array(
            'evento'        => $evento,
            'participacoes' => $participacoes
        ));
    }

    /**
     * Envia um email para o participante com o seu certificado de participação
     */
    public function enviarCertificado2Action(){

        /** @var  $participacao \Application\Entity\Participacao */
        $participacao  = $this->getEm()->getRepository('Application\Entity\Participacao')
            ->find($this->params('id'));

        $modelo_array = (new Certificado($this->getEm()))->getModeloComTexto($participacao);

        if (is_null($modelo_array)) {
            $this->flashMessenger()
                ->setNamespace(FlashMessenger::NAMESPACE_WARNING)
                ->addMessage('Não há nenhum modelo de certificados compativel com essa participação');

            return $this->redirect()->toRoute('participacao/default', array(
                'action' => 'enviar-certificados-por-email',
                'evento' => $participacao->getAtividade()->getEvento()->getId()
            ));
        }

        /**
         * Criar o arquivo a ser enviado no email
         */
        $dompdf = new Dompdf();

        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        $layout = new ViewModel();
        $layout->setTemplate("application/certificado/download");


        // Evita dependência de URL remota (SERVER_NAME sem porta) no Dompdf.
        $imgPath = dirname(__FILE__) . '/../../../../../public_html/assets/certificados/frente/' . $modelo_array['bg_frente'];
        $imgPath = realpath($imgPath) ?: $imgPath;
        $mime = is_file($imgPath) ? (mime_content_type($imgPath) ?: ('image/' . pathinfo($imgPath, PATHINFO_EXTENSION))) : null;
        $dataUri = '';
        if (is_file($imgPath)) {
            $data = file_get_contents($imgPath);
            $dataUri = 'data:' . $mime . ';base64,' . base64_encode($data);
        }

        $layout->setVariable("fundo", $dataUri);
        $layout->setVariable("modelo", $modelo_array);
        $layout->setVariable("participacao", $participacao);

        $html = $viewRender->render($layout);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->getOptions()->set('dpi', '200');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $nome_do_arquivo = 'certificado-' . $participacao->getChaveValidacao().'.pdf';

        $output = $dompdf->output();

        $path_file  = realpath(dirname(__FILE__). '/../../../../../data/temp'). DIRECTORY_SEPARATOR. $nome_do_arquivo.'.pdf';
        $arquivo_foi_gerado = file_put_contents($path_file, $output);

        //se o arquivo não foi gerado
        if ($arquivo_foi_gerado ==  false) {
            $this->flashMessenger()
                ->setNamespace(FlashMessenger::NAMESPACE_WARNING)
                ->addMessage('Não foi possível gerar o arquivo  '. $nome_do_arquivo);

            return $this->redirect()->toRoute('participacao/default', array(
                'action' => 'enviar-certificados-por-email',
                'evento' => $participacao->getAtividade()->getEvento()->getId()
            ));
        }

        //envia o emal
        $mail = new Mail();

        //Recipients
        $mail->setFrom('weekit.vdc@ifba.edu.br', 'Certificados Online - IFBA Vitória da Conquista');
        $mail->addAddress($participacao->getParticipante()->getEmail(), $participacao->getParticipante()->getNomeCompleto());

        //Attachments
        $mail->addAttachment($path_file, $nome_do_arquivo);    // Optional name

        //Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $participacao->getAtividade()->getEvento()->getSigla(). ' - '. $participacao->getAtividade()->getEvento() . ' -'.$participacao->getAtividade()->getEvento()->getAno().  ': ' . $participacao->getAtividade()->getTitulo();

        $mail->Body    = '<b>Participante:</b> '. $participacao->getParticipante()->getNomeCompleto() . '<br/>';
        $mail->Body   .= '<b>Evento:</b> '. $participacao->getAtividade()->getEvento(). '<br/>';
        $mail->Body   .= '<b>Atividade:</b> '. $participacao->getAtividade()->getTitulo(). '<br/>';
        $mail->Body   .= '<b>Função:</b> '. $participacao->getFuncao()->getNome(). '<br/>';


        if($mail->send()){

            (new Participacao($this->getEm()))->update(array(
                'id' => $participacao->getId(),
                'emailEnviadoEm' => new \DateTime('now')
            ));

            unlink($path_file);

            $this->flashMessenger()
                ->setNamespace(FlashMessenger::NAMESPACE_SUCCESS)
                ->addMessage('Certificado enviado com sucesso para '. $participacao->getParticipante()->getEmail() );

            return $this->redirect()->toRoute('participacao/default', array(
                'action' => 'enviar-certificados-por-email',
                'evento' => $participacao->getAtividade()->getEvento()->getId()
            ));
        }

    }

    public function enviarCertificadoAction(){

            $service = new Certificado($this->getEm());

            /** @var  $participacao \Application\Entity\Participacao */
            $participacao  = $this->getEm()->getRepository('Application\Entity\Participacao')->find($this->params('id'));

            if (is_null($participacao)) {
                $this->flashMessenger()
                    ->setNamespace(FlashMessenger::NAMESPACE_ERROR)
                    ->addMessage('A participação informada não existe');

                return $this->redirect()->toRoute('participacao/default', array(
                    'action' => 'enviar-certificados-por-email',
                    'evento' => $participacao->getAtividade()->getEvento()->getId()
                ));
            }

            $modelo_array = $service->getModeloComTexto($participacao);

            if (is_null($modelo_array))
            {
                $this->flashMessenger()
                    ->setNamespace(FlashMessenger::NAMESPACE_ERROR)
                    ->addMessage('Não há nenhum modelo de certificados compativel com essa participação. Entre em contato com o administrador');

                return $this->redirect()->toRoute('participacao/default', array(
                    'action' => 'enviar-certificados-por-email',
                    'evento' => $participacao->getAtividade()->getEvento()->getId()
                ));
            }

            $arquivo = $service->getPdf($this->getServiceLocator()->get('ViewRenderer'),  $participacao, $modelo_array);

            //se o arquivo não foi gerado
            if ($arquivo ==  false)
            {
                $this->flashMessenger()
                    ->setNamespace(FlashMessenger::NAMESPACE_ERROR)
                    ->addMessage('Não foi possível gerar o arquivo  '. $arquivo['name']);

                return $this->redirect()->toRoute('participacao/default', array(
                    'action' => 'enviar-certificados-por-email',
                    'evento' => $participacao->getAtividade()->getEvento()->getId()
                ));
            }

            $envio = $service->enviarCertificadoPorEmail($participacao, $arquivo['name'], $arquivo['path']);


            if($envio  === true)
            {
                $this->flashMessenger()
                    ->setNamespace(FlashMessenger::NAMESPACE_SUCCESS)
                    ->addMessage( 'Certificado enviado com sucesso para '. $participacao->getParticipante(). "<". $participacao->getParticipante()->getEmail().">");

                return $this->redirect()->toRoute('participacao/default', array(
                    'action' => 'enviar-certificados-por-email',
                    'evento' => $participacao->getAtividade()->getEvento()->getId()
                ));

            }else {
                $this->flashMessenger()
                    ->setNamespace(FlashMessenger::NAMESPACE_ERROR)
                    ->addMessage( 'Um erro ocorreu ao enviar  '. $participacao->getParticipante()->getEmail(). " (".$envio.")");

                return $this->redirect()->toRoute('participacao/default', array(
                    'action' => 'enviar-certificados-por-email',
                    'evento' => $participacao->getAtividade()->getEvento()->getId()
                ));
            }
    }

    public function enviarCertificadoAjaxAction(){

        $service = new Certificado($this->getEm());

        /** @var  $participacao \Application\Entity\Participacao */
        $participacao  = $this->getEm()->getRepository('Application\Entity\Participacao')->find($this->params('id'));

        if (is_null($participacao)) {
            return new JsonModel(array(
                'code'       => 404,
                'message'    => 'A participação informada não existe'
            ));
        }

        $modelo_array = $service->getModeloComTexto($participacao);

        if (is_null($modelo_array))
        {
            return new JsonModel(array(
                'code'       => 404,
                'message'    => 'Não há nenhum modelo de certificados compativel com essa participação. Entre em contato com o administrador'
            ));
        }

        $arquivo = $service->getPdf($this->getServiceLocator()->get('ViewRenderer'),  $participacao, $modelo_array);

        //se o arquivo não foi gerado
        if ($arquivo ==  false) {
            return new JsonModel(array(
                'code'       => 404,
                'message'    => 'Não foi possível gerar o arquivo  '. $arquivo['name']
            ));
        }

        if($service->enviarCertificadoPorEmail($participacao, $arquivo['name'], $arquivo['path']) == true) {
            return new JsonModel(array(
                'code'       => 200,
                'message'    => 'Certificado enviado com sucesso'
            ));
        }else {
            return new JsonModel(array(
                'code'       => 404,
                'message'    => 'Um erro ocorreu ao enviar  '. $participacao->getParticipante()->getEmail()
            ));
        }

    }
}

