<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Datax extends CI_Controller
{    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    public function index()
    {

    }

    public function getTable()
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('citas.id', 'clients.nombre_completo', 'citas.fecha_inicio', 'citas.hora_inicio', 'clients.id', 'user.first_name');
        
        // DB table to use
        $sTable = 'citas';
        //$sTable2 = 'user';
        //
$sucursal = $this->session->userdata('sucursal')->id;
        $iDisplayStart = $this->input->get_post('iDisplayStart', true);
        $iDisplayLength = $this->input->get_post('iDisplayLength', true);
        $iSortCol_0 = $this->input->get_post('iSortCol_0', true);
        $iSortingCols = $this->input->get_post('iSortingCols', true);
        $sSearch = $this->input->get_post('sSearch', true);
        $sEcho = $this->input->get_post('sEcho', true);
        $this->db->where('citas.sucursal', $sucursal);
        // Paging
        if(isset($iDisplayStart) && $iDisplayLength != '-1')
        {
            $this->db->limit($this->db->escape_str($iDisplayLength), $this->db->escape_str($iDisplayStart));
        }
        
        // Ordering
        if(isset($iSortCol_0))
        {
            for($i=0; $i<intval($iSortingCols); $i++)
            {
                $iSortCol = $this->input->get_post('iSortCol_'.$i, true);
                $bSortable = $this->input->get_post('bSortable_'.intval($iSortCol), true);
                $sSortDir = $this->input->get_post('sSortDir_'.$i, true);
    
                if($bSortable == 'true')
                {
                    $this->db->order_by($aColumns[intval($this->db->escape_str($iSortCol))], $this->db->escape_str($sSortDir));
                }
            }
        }
        
        /* 
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        if(isset($sSearch) && !empty($sSearch))
        {
            for($i=0; $i<count($aColumns); $i++)
            {
                $bSearchable = $this->input->get_post('bSearchable_'.$i, true);
                
                // Individual column filtering
                if(isset($bSearchable) && $bSearchable == 'true')
                {
					$this->db->or_like($aColumns[$i], $this->db->escape_like_str($sSearch));
                }
            }
        }


		/*
		 * Select list
		 */
		$sSelect = "";
		for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
			$sSelect .= $aColumns[$i] .' as `'.$aColumns[$i].'`, ';
		}
		$sSelect = substr_replace( $sSelect, "", -2 );

		// Select Data			str_replace(' , ', ' ', implode(', ', $aColumns)
        $this->db->select('SQL_CALC_FOUND_ROWS '.$sSelect, false);
        $this->db->join('clients', 'citas.paciente = clients.id');
        $this->db->join('user', 'citas.atiende = user.user_id');
		//$this->db->where('citas.fecha_inicio = "2013-10-28"');
        $this->db->where('citas.status = "vigente"');
         $this->db->where('citas.sucursal',$sucursal);
        $rResult = $this->db->get($sTable);

    
        // Data set length after filtering
        $this->db->select('FOUND_ROWS() AS found_rows');

        $iFilteredTotal = $this->db->get()->row()->found_rows;
    
        // Total data set length
        $this->db->where('citas.sucursal',$sucursal);
        
        $iTotal = $this->db->count_all_results($sTable);
    

    
        // Output
        $output = array(
            'sEcho' => intval($sEcho),
            'iTotalRecords' => $iTotal,
            'iTotalDisplayRecords' => $iFilteredTotal,
            'aaData' => array()
        );
        
        foreach($rResult->result_array() as $aRow)
        {
            $row = array();
            
            foreach($aColumns as $col)
            {
                $row[] = $aRow[$col];
            }
    
            $output['aaData'][] = $row;
        }
        echo json_encode($output);
    }
}
?>