<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

class PlgFabrik_ListUpdate_At extends PlgFabrik_List
{
    // essa funcao é chamada sempre que a lista é aberta 
    public function onLoadData()
    {
        $model = $this->getModel();
        $user = JFactory::getUser(); // pega os dados do usuario que esta acessando
        // só chama a função se for administrador
        if ($user->authorise('core.admin')) {
            $this->gravarDados();
        }
    }

    public function gravarDados()
    {
        // Conexão com o banco de dados Joomla
        $db = JFactory::getDbo();
        // Seleciona todos os dados da tabela fabrik_logs
        $query = $db->getQuery(true);
        $query->select('*')->from($db->quoteName('joomla_fabrik_log'))->order('timedate_created DESC');
        $db->setQuery($query);
        $logs = $db->loadAssocList();
        $lastUpdate = [];
        //faz um foreach nos dados encontratos para montar um array com as info necessarias
        foreach ($logs as $log) {
            $remove = 'https://edu.cett.dev.br/';
            $parts  = explode('/', str_replace($remove, '', $log['referring_url']));
            //se não existe ainda o id da solicitação no array ele adicionar junto com a data (isso para pegar apenas o ultimo registro)
            if (!isset($lastUpdate[$parts[3]]) && intval($parts[3])) {
                $lastUpdate[$parts[3]] = $log['timedate_created'];
            }
        }
        $atualizados = [];
        //faz um foreach no array formado para inserir no banco de dados
        foreach ($lastUpdate as $id => $data) {
            try {
                $query = $db->getQuery(true);
                $query->update($db->quoteName('edu_solicitacoes'))
                    ->set($db->quoteName('updated_date') . ' = ' . $db->quote($data))
                    ->where($db->quoteName('id') . ' = ' . $db->quote($id));
                // Executar a consulta de atualização
                $db->setQuery($query);
                $db->execute();
                if ($db->getAffectedRows() > 0) {
                    $atualizados['atualizados'] = $id;
                  } else {
                    $atualizados['sem_registro'] = $id;
                  }
            } catch (Exception $err) {
                $atualizados['erro'] = $id;
                // echo "<pre>";
                // print_r($err);
            }
        }
        echo "<script>console.log(" . json_encode($atualizados) . ")</script>";
    }
}
