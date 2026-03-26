<?php
namespace Application\Service;

use Base\Service\AbstractService;
use Doctrine\ORM\EntityManager;
use Dompdf\Dompdf;
use DOMPDFModule\View\Model\PdfModel;
use PHPMailer\PHPMailer\PHPMailer;
use Zend\Filter\StringTrim;
use Zend\Crypt\PublicKey\Rsa\PublicKey;
use Zend\Mvc\Controller\Plugin\FlashMessenger;
use Zend\View\Model\ViewModel;
use Zend\Hydrator;

class Certificado extends AbstractService
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        
        $this->entity = 'Application\Entity\Atividade';
        $this->errorCodeValidator = [
            1451 => 'Para excluir essa atividade você deverá excluir todas as participações a ela vinculada'
        ];
        
        $this->filterStringTrim = new StringTrim();
    }

    /**
     * Retorna o modelo do certificado para a participaÃ§Ã£o seleciooda.
     * Para as participação
     * que não tem um modelo que configurado ele retornarÃ¡ nulo
     *
     * @param \Application\Entity\Participacao $participacao
     * @return array
     */
    public function getModeloComTexto(\Application\Entity\Participacao $participacao)
    {

        $sql = "SELECT modelo_certificado.id
                 FROM modelo_certificado, certificado_tipo_funcao_evento
                 where modelo_certificado.id = certificado_tipo_funcao_evento.modelo_certificado_id
                 and  certificado_tipo_funcao_evento.tipo_atividade_id = " . $participacao->getAtividade()->getTipoAtividade()->getId() . "
                 and certificado_tipo_funcao_evento.funcao_id =  " . $participacao->getFuncao()->getId() . "
                 and certificado_tipo_funcao_evento.evento_id = ". $participacao->getAtividade()->getEvento()->getId().
                 " GROUP by modelo_certificado.id
                 LIMIT 1 ";
        try{
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute();
        }catch (\Exception $exception){
            var_dump($exception->getMessage());die;
            return null;
        }

        $id = 0;
        
        $result = $stmt->fetchAll();


        if (empty($result)) {
            return NULL;
        }


        foreach ($result as $ids) {
            $id = $ids['id'];
        }
        
        /** @var  $modelo \Application\Entity\ModeloCertificado */
        $modelo = $this->em->getRepository('Application\Entity\ModeloCertificado')->findOneBy(array(
            'id' => $id
        ));

        if(is_null($modelo)){
            return NULL;
        }

        $array                  = $modelo->toArray();
        $array['texto_frente']  = $this->replaceTextoFrente($modelo, $participacao);
        $array['texto_verso']   = $this->replaceTextoVerso($modelo, $participacao);

        return $array;
    }

    /**
     *
     * @param \Application\Entity\ModeloCertificado $modeloCertificado
     * @param \Application\Entity\Participacao $participacao
     * @return array
     */
    public function replaceTextoFrente(\Application\Entity\ModeloCertificado $modeloCertificado, \Application\Entity\Participacao $participacao)
    {

        $texto = $modeloCertificado->getTextoFrente();

        foreach ($this->getTagsArray() as $tag) {
            $texto = str_replace($tag, $this->getValueTag($tag, $participacao), $texto);
        }


        return $texto;
    }


    public function replaceTextoVerso(\Application\Entity\ModeloCertificado $modeloCertificado, \Application\Entity\Participacao $participacao)
    {
        $texto = $modeloCertificado->getTextoVerso();

        foreach ($this->getTagsArray() as $tag) {
            $texto = str_replace($tag, $this->getValueTag($tag, $participacao), $texto);
        }

        //$modeloCertificado->setTextoVerso($texto);


        return $texto;
    }




    /**
     * Retorna o valor da tag
     * 
     * @param
     *            $tag
     * @param \Application\Entity\Participacao $participacao
     * @return int|string
     */
    public function getValueTag($tag, \Application\Entity\Participacao $participacao)
    {
        $string = '';
        $encoding = mb_internal_encoding();

        switch ($tag) {
            case "[participante_nome]":
                $string =  $participacao->getParticipante()->getNomeCompleto();
                break;
            case "[PARTICIPANTE_NOME]":
                $string =  $participacao->getParticipante()->getNomeCompleto();
                $string = mb_strtoupper($string, $encoding);
                break;
            case "[participante_data_nascimento]":
                $string =  $participacao->getParticipante()
                    ->getDataNascimento()
                    ->format('d/m/Y');
                break;
            case "[participante_cpf]":
                $cpf = $participacao->getParticipante()->getCpf();
                $string =  substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                break;
            case "[evento_nome]":
                $string =  $participacao->getAtividade()
                    ->getEvento()
                    ->getNome();
                break;
            case "[EVENTO_NOME]":
                $string =  $participacao->getAtividade()
                    ->getEvento()
                    ->getNome();
                $string = mb_strtoupper($string, $encoding);
                break;
            case "[evento_sigla]":
                $string =  $participacao->getAtividade()
                    ->getEvento()
                    ->getSigla();

                break;
            case "[EVENTO_SIGLA]":
                $string =  $participacao->getAtividade()
                    ->getEvento()
                    ->getSigla();
                $string = mb_strtoupper($string, $encoding);
                break;
            case "[evento_ano]":
                $string =  $participacao->getAtividade()
                    ->getEvento()
                    ->getAno();
                break;
            case "[evento_periodo]":
                $data_inicio = $participacao->getAtividade()->getEvento()->getDataInicial();
                $data_fim = $participacao->getAtividade()->getEvento()->getDataFinal();

                $string =  $this->getDataFormatada($data_inicio, $data_fim);
                break;
            case "[atividade_titulo]":
                $string =  $participacao->getAtividade()->getTitulo();
                break;

            case "[ATIVIDADE_TITULO]":

                $string =  $participacao->getAtividade()->getTitulo();
                $string = mb_strtoupper($string, $encoding);
                break;

            case "[atividade_periodo]":
                
                $data_inicio = $participacao->getAtividade()->getDataInicio();
                $data_fim = $participacao->getAtividade()->getDataFim();

                $string =  $this->getDataFormatada($data_inicio, $data_fim);
                
                break;
            case "[atividade_carga_horaria]":
                if($participacao->getAtividade()->getCargaHoraria() == 1){
                    $string =  $participacao->getAtividade()->getCargaHoraria() . ' hora';
                }else{
                    $string =  $participacao->getAtividade()->getCargaHoraria() . ' horas';
                }
                break;
            case "[funcao_nome]":
                $string =  $participacao->getFuncao()->getNome();
                break;
            case "[FUNCAO_NOME]":
                $string =  $participacao->getFuncao()->getNome();
                $string = mb_strtoupper($string, $encoding);
                break;
            case "[participacao_carga_horaria]":
                if($participacao->getCargaHoraria() == 1){
                    $string =  $participacao->getCargaHoraria(). ' hora';
                }else{
                    $string =  $participacao->getCargaHoraria(). ' horas';
                }
                break;
            case "[participacao_qtd_bolsita]":
                $string =  $this->convert_number_to_words($participacao->getQtdBolsista());
                break;
            case "[participacao_periodo]":

                $data_inicio = $participacao->getDataInicio();
                $data_fim = $participacao->getDataFim();

                $string =  $this->getDataFormatada($data_inicio, $data_fim);

                break;
        }


        return $string;


    }

    /**
     * Retorna um array com todas as tags disponiveis para a geraÃ§Ã£o dos certificados
     * 
     * @return array
     */
    public function getTagsArray()
    {
        $array =  [
            "[participante_nome]",
            "[evento_nome]",
            "[evento_sigla]",
            "[evento_ano]",
            "[evento_periodo]",
            "[atividade_titulo]",
            "[atividade_carga_horaria]",
            "[atividade_periodo]",
            "[funcao_nome]",
            "[participacao_carga_horaria]",
            "[participacao_qtd_bolsita]",
            "[participacao_periodo]"
        ];

        return array_merge($array, array_map('strtoupper', $array));

    }

    public function getDataFormatada(\DateTime $data_inicio, \DateTime $data_fim)
    {
        
        // se a data Ã© extatamente igual
        if ($data_inicio->format('d/m/y') == $data_fim->format('d/m/y')) {
            return $data_inicio->format('d') . ' de ' . $this->getMesExtenso($data_inicio->format('m')) . ' de ' . $data_inicio->format('Y');
        }
        
        // se o ano e mês são iguais e o dia não
        if ($data_inicio->format('m/y') == $data_fim->format('m/y')) {
            return $data_inicio->format('d') . ' a ' . $data_fim->format('d') . ' de ' . $this->getMesExtenso($data_inicio->format('m')) . ' de ' . $data_inicio->format('Y');
        }
        
        // se o ano sÃ£o iguais mas mes e dia nÃ£o diferentes
        if ($data_inicio->format('y') == $data_fim->format('y')) {
            return $data_inicio->format('d') . ' de ' . $this->getMesExtenso($data_inicio->format('m')) . ' a ' . $data_fim->format('d') . ' de ' . $this->getMesExtenso($data_fim->format('m')) . ' de ' . $data_fim->format('Y');
        }
        
        return $data_inicio->format('d') . ' de ' . $this->getMesExtenso($data_inicio->format('m')) . ' de ' . $data_inicio->format('Y') . ' a ' . $data_fim->format('d') . ' de ' . $this->getMesExtenso($data_fim->format('m')) . ' de ' . $data_fim->format('Y');
    }

    public function getMesExtenso($mes_numero)
    {
        $mes = null;
        
        switch ($mes_numero) {
            case "01":
                $mes = "janeiro";
                break;
            case "02":
                $mes = "fevereiro";
                break;
            case "03":
                $mes = "março";
                break;
            case "04":
                $mes = "abril";
                break;
            case "05":
                $mes = "maio";
                break;
            case "06":
                $mes = "junho";
                break;
            case "07":
                $mes = "julho";
                break;
            case "08":
                $mes = "agosto";
                break;
            case "09":
                $mes = "setembro";
                break;
            case "10":
                $mes = "outubro";
                break;
            case "11":
                $mes = "novembro";
                break;
            case "12":
                $mes = "dezembro";
                break;
        }
        
        return $mes;
    }

    function convert_number_to_words($number) {

        $hyphen      = '-';
        $conjunction = ' e ';
        $separator   = ', ';
        $negative    = 'menos ';
        $decimal     = ' ponto ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'um',
            2                   => 'dois',
            3                   => 'três',
            4                   => 'quatro',
            5                   => 'cinco',
            6                   => 'seis',
            7                   => 'sete',
            8                   => 'oito',
            9                   => 'nove',
            10                  => 'dez',
            11                  => 'onze',
            12                  => 'doze',
            13                  => 'treze',
            14                  => 'quatorze',
            15                  => 'quinze',
            16                  => 'dezesseis',
            17                  => 'dezessete',
            18                  => 'dezoito',
            19                  => 'dezenove',
            20                  => 'vinte',
            30                  => 'trinta',
            40                  => 'quarenta',
            50                  => 'cinquenta',
            60                  => 'sessenta',
            70                  => 'setenta',
            80                  => 'oitenta',
            90                  => 'noventa',
            100                 => 'cento',
            200                 => 'duzentos',
            300                 => 'trezentos',
            400                 => 'quatrocentos',
            500                 => 'quinhentos',
            600                 => 'seiscentos',
            700                 => 'setecentos',
            800                 => 'oitocentos',
            900                 => 'novecentos',
            1000                => 'mil',
            1000000             => array('milhão', 'milhões'),
            1000000000          => array('bilhão', 'bilhões'),
            1000000000000       => array('trilhão', 'trilhões'),
            1000000000000000    => array('quatrilhão', 'quatrilhões'),
            1000000000000000000 => array('quinquilhão', 'quinquilhões')
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words só aceita números entre ' . PHP_INT_MAX . ' à ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $conjunction . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = floor($number / 100)*100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds];
                if ($remainder) {
                    $string .= $conjunction . convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                if ($baseUnit == 1000) {
                    $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[1000];
                } elseif ($numBaseUnits == 1) {
                    $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit][0];
                } else {
                    $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit][1];
                }
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= convert_number_to_words($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    public function enviarCertificadoPorEmail(\Application\Entity\Participacao $participacao, $nome_do_arquivo, $path_file){

        //envia o emal
        $mail = new Mail();


        //Recipients
        $mail->setFrom('weekit.vdc@ifba.edu.br', 'Certificados Online - IFBA Vitória da Conquista');
        $mail->addAddress($participacao->getParticipante()->getEmail(), $participacao->getParticipante()->getNomeCompleto());

        //Attachments
        $mail->addAttachment($path_file, $nome_do_arquivo);    // Optional name

        //Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'Certificado: ' . $participacao->getAtividade()->getEvento()->getSigla(). ' - '. $participacao->getAtividade()->getEvento()->getNome() . ' '. $participacao->getAtividade()->getEvento()->getAno().  ': ' . $participacao->getAtividade()->getTitulo();

        $mail->Body    = 'O certificado do evento ' . $participacao->getAtividade()->getEvento(). ' encontra-se disponível em anexo. <br/><br/>
             Caso deseja emitir ou validar o certificado acessar o sistema no link:<br/>
              <a href="http://certificados.ifba.edu.br/">
              http://certificados.ifba.edu.br/
              </a>.<br/>
             At.te,<br/><br/>
             Sistema de Emissão e Validação de Certificados<br/>
             IFBA Campus Vitória da Conquista<br/>';

        try{
            if($mail->send()){

                (new Participacao($this->em))->update(array(
                    'id' => $participacao->getId(),
                    'emailEnviadoEm' => new \DateTime('now')
                ));

                unlink($path_file);

                return true;
            }else{
                (new Participante($this->em))->update(array(
                    'id' => $participacao->getParticipante()->getId(),
                    'email_valido' => 0
                ));

                return false;
            }
        }catch (\Exception $exception){
            return $exception->getMessage();
        }
    }

    public function gerarCertificado($modelo_array, \Application\Entity\Participacao $participacao, $forcar_download = false){

        $nome_do_arquivo = 'certificado-' . $participacao->getChaveValidacao();
        $pdf = new PdfModel();

        $pdf->setOption('display', PdfModel::DISPLAY_ATTACHMENT); // Triggers browser to prompt "save as" dialog

        $pdf->setOption('fileName', $nome_do_arquivo); // "pdf" extension is automatically appended
        $pdf->setOption('basePath', 'yy/');

        $pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
        $pdf->setOption('dpi', 200);
        $pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

        // Gera uma data URI para o fundo do certificado.
        // Importante: evitar URL remota (SERVER_NAME/porta) no Dompdf; usamos caminho local.
        $imgPath = dirname(__FILE__) . '/../../../../../public_html/assets/certificados/frente/' . $modelo_array['bg_frente'];
        $imgPath = realpath($imgPath) ?: $imgPath;
        $mime = is_file($imgPath) ? (mime_content_type($imgPath) ?: ('image/' . pathinfo($imgPath, PATHINFO_EXTENSION))) : null;
        $dataUri = '';
        if (is_file($imgPath)) {
            $data = file_get_contents($imgPath);
            $dataUri = 'data:' . $mime . ';base64,' . base64_encode($data);
        }

        $pdf->setVariable('fundo', $dataUri);
        $pdf->setVariable('modelo', $modelo_array);
        $pdf->setVariable('participacao', $participacao);

        //atualiza data da ultima emissão

        try {
            (new Hydrator\ClassMethods())->hydrate(array(
                'data_ultima_emissao' => new \DateTime('now')
            ), $participacao);

            $this->em->flush($participacao);
        } catch (\Doctrine\DBAL\Exception\DriverException $exception) {
            return $this->exceptionMenssage($exception->getErrorCode(), $exception->getMessage());
        } catch (\Exception $exception) {
            return $this->exceptionMenssage($exception->getCode(), $exception->getMessage());
        }
        

        return $pdf;
    }


    public function getPdf($viewRender, \Application\Entity\Participacao $participacao, $modelo_array){

        $dompdf = new Dompdf();

        $layout = new ViewModel();
        $layout->setTemplate("application/certificado/download");

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

        if($arquivo_foi_gerado != false)
            return array(
                'name' => $nome_do_arquivo,
                'path' => $path_file
            );

        return false;
    }
}

