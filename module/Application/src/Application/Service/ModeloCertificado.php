<?php
namespace Application\Service;

use Base\Service\AbstractService;
use Doctrine\ORM\EntityManager;
use \Zend\Filter\File\Rename;

class ModeloCertificado extends AbstractService
{

    private $path_folder_modelo_certificado_frente;
    private $path_folder_modelo_certificado_verso;

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        
        $this->entity = 'Application\Entity\ModeloCertificado';
        $this->errorCodeValidator = [
            1451 => 'Esse modelo está sendo utilizado em um evento e por isso não pode ser excluido'
        ];
        $this->path_folder_modelo_certificado_frente = $this->resolveStoragePath('/../../../../../public_html/assets/certificados/frente');
        $this->path_folder_modelo_certificado_verso = $this->resolveStoragePath('/../../../../../public_html/assets/certificados/verso');
    }

    public function insert($data)
    {

        $array_bgFrente = $data['bgFrente'];
        unset($data['bgFrente']);

        $array_bgVerso = $data['bgVerso'];
        unset($data['bgVerso']);

        if($data['tipo']== 'frente_verso'){
            unset($data['texto_verso ']);
        }

        /** @var  $entity \Application\Entity\ModeloCertificado*/
        $entity = parent::insert($data);

        //Enviar arquivo da frente
        $data['bgFrente'] = $entity->getId().'.'. pathinfo($array_bgFrente['name'], PATHINFO_EXTENSION);
        $this->enviarArquivo($this->path_folder_modelo_certificado_frente . $data['bgFrente'], $array_bgFrente);



        //enviar verso se ele existir
        if($array_bgVerso['size'] != 0 && $data['tipo'] == 'frente_verso'){
            $data['bgVerso'] = $entity->getId().'.'. pathinfo($array_bgVerso['name'], PATHINFO_EXTENSION);
            $this->enviarArquivo($this->path_folder_modelo_certificado_verso . $data['bgVerso'], $array_bgVerso);
        }

        $data['id']  = $entity->getId();
        $entity = parent::update($data);

        return $entity;
    }

    public function enviarArquivo($full_path, $array_file){
        $dir = dirname($full_path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new \RuntimeException("Nao foi possivel criar diretorio de upload: {$dir}");
        }

        $filter = new Rename(array(
            "target" => $full_path,
            "overwrite" => true
        ));

        try {
            $filter->filter($array_file);
            return;
        } catch (\Exception $e) {
            $tmp = isset($array_file['tmp_name']) ? $array_file['tmp_name'] : null;
            if (!is_string($tmp) || $tmp === '' || !file_exists($tmp)) {
                throw $e;
            }

            // Fallback para ambientes em que rename entre fs/volumes falha.
            if (@move_uploaded_file($tmp, $full_path) || @rename($tmp, $full_path) || @copy($tmp, $full_path)) {
                if (file_exists($tmp) && realpath($tmp) !== realpath($full_path)) {
                    @unlink($tmp);
                }
                return;
            }

            throw $e;
        }
    }



    public function update($data)
    {


        $array_bgFrente = $data['bgFrente'];
        unset($data['bgFrente']);

        $array_bgVerso = $data['bgVerso'];
        unset($data['bgVerso']);

        if($data['tipo']== 'frente_verso'){
            unset($data['texto_verso ']);
        }

        /** @var  $entity \Application\Entity\ModeloCertificado*/
        $entity = parent::update($data);

        if($array_bgFrente['size'] != 0) {
            $data['bgFrente'] = $entity->getId() . '.' . pathinfo($array_bgFrente['name'], PATHINFO_EXTENSION);
            $this->enviarArquivo($this->path_folder_modelo_certificado_frente . $data['bgFrente'], $array_bgFrente);
        }

        //enviar verso se ele existir
        if($array_bgVerso['size'] != 0 && $data['tipo'] == 'frente_verso'){
            $data['bgVerso'] = $entity->getId().'.'. pathinfo($array_bgVerso['name'], PATHINFO_EXTENSION);
            $this->enviarArquivo($this->path_folder_modelo_certificado_verso . $data['bgVerso'], $array_bgVerso);
        }

        $data['id']  = $entity->getId();
        $entity = parent::update($data);

        return $entity;
    }

    public function delete($id)
    {
        /** @var  $entity \Application\Entity\ModeloCertificado */
        $entity = $this->em->getRepository($this->entity)->find($id);

        $result =  parent::delete(array(
            'id' => $id
        ));

        if(!is_null($result)){
            $path_file_frente  = $this->path_folder_modelo_certificado_frente . $entity->getBgFrente();
            $path_file_verso   = $this->path_folder_modelo_certificado_verso . $entity->getBgVerso();

            if(file_exists($path_file_frente && $entity->getBgFrente() != null)){
                unlink($path_file_frente);
            }

            if(file_exists($path_file_verso) && ($entity->getBgVerso() != null)){
                unlink($path_file_verso);
            }
        }

        return $result;
    }

    private function resolveStoragePath($suffix)
    {
        $basePath = dirname(__FILE__) . $suffix;
        $normalized = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $normalized;
    }

}

