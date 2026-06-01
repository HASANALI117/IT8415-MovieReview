<?php
class Database
{
    private $LOCAL_DB=0;

    protected  $dbc = NULL;

    public function getConnection()
    {
        if (!$this->LOCAL_DB){
            if($this->dbc==NULL)
              $this->dbc = mysqli_connect('localhost','root','asdASD123456', 'movie_review');

           if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                die('b0ther');
            }
        }
        else
        {
          $this->dbc = mysqli_connect('localhost','root','asdASD123456', 'movie_review');
           if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                die('b0ther');
            }
        }

        return $this->dbc;
    }

    public function closeDB()
    {
         mysqli_close($this->dbc);
    }
}
?>
