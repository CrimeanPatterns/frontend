<?
require "../web/kernel/public.php";

class AccountsCounter{
    
    var $maxCharacterTAccount;
    var $maxCharacterTAccountProperty;
    
    function __construct(){
        $this->maxCharacterAccount = $this->getMaxCharacterAccount();
        $this->maxCharacterAccountProperty = $this->getMaxCharacterAccountProperty();
    }
    
    public function getMaxCharacterAccount(){
        $sql = "
            SELECT
                MAX(LENGTH(login)) maxLength
            FROM Account
        ";        
        $q = new TQuery($sql);
        if (!$q->EOF)
            return $q->Fields['maxLength'];        
        return 0;
    }
    
    public function getMaxCharacterAccountProperty(){
        $sql = "
            SELECT 
                MAX(LENGTH(Val)) maxLength
            FROM 
                AccountProperty
                JOIN (
                    SELECT 
                        ProviderPropertyID 
                    FROM 
                        ProviderProperty 
                    WHERE CODE = 'Number'
                ) ids USING(ProviderPropertyID)
        ";        
        $q = new TQuery($sql);
        if (!$q->EOF)
            return $q->Fields['maxLength'];        
        return 0;
    }
    
    public function getCountCharactersAccount(){
        $sql = "
            SELECT
        ";
        for($i = 2; $i <= $this->maxCharacterAccount; $i++){
            $sql .= "
                SUM(IF(LENGTH(Login)={$i},1,0)) {$i}_characters,";
        }
        $sql = trim($sql,',');
        $sql .= " 
            FROM Account
        ";        
        $q = new TQuery($sql);
        if (!$q->EOF){
            echo "Result for Account:\n";
            foreach($q->Fields as $key=>$val){
                if ($val > 0)
                    echo str_replace('_',' ',$key).': '.$val." IDs\n";
            }
        }
    }
    
    public function getCountCharactersAccountProperty(){
        $sql = "
            SELECT
        ";
        for($i = 2; $i <= $this->maxCharacterAccountProperty; $i++){
            $sql .= "
                SUM(IF(LENGTH(Val)={$i},1,0)) {$i}_characters,";
        }
        $sql = trim($sql,',');
        $sql .= " 
            FROM 
            AccountProperty
            JOIN (
                SELECT 
                    ProviderPropertyID 
                FROM 
                    ProviderProperty 
                WHERE CODE = 'Number'
            ) ids USING(ProviderPropertyID)
        ";        
        $q = new TQuery($sql);
        if (!$q->EOF){
            echo "Result for AccountProperty:\n";
            foreach($q->Fields as $key=>$val){
                if ($val > 0)
                    echo str_replace('_',' ',$key).': '.$val." IDs\n";
            }
        }
    }
    
}

$Counter = new AccountsCounter();
$Counter->getCountCharactersAccount();
echo "\n-----------------------------------------------------------\n\n";
$Counter->getCountCharactersAccountProperty();



?>
