<?php
namespace App\KSI\JsonBuilder;

use Illuminate\Support\Facades\Input;

/**
 * Class ModelWalker
 * This Walker is for fast based Modle Generate Json object with pagination
 * or to get only one item with abilty to travel and build all defined relations
 * @package App\KSI\JsonBuilder
 * @Author : Youssef Jad
 */
trait ModelWalker {

	public function getColumns($table){
		return $columns = \Schema::getColumnListing($table);
	}

	public function BulidJson( $object , $class , $relations = false )
	{
		$data = [];
		if($object instanceof \Illuminate\Pagination\LengthAwarePaginator ){

			foreach ($object as $key => &$value ){
				$data['data'][$key] = new \stdClass();
				$columns = $this->getColumns($this->table);
				foreach ($columns as $arrKey => $arrValue ){
					$data['data'][$key]->{$arrValue} = $value->{$arrValue} ?? '' ;
					if( $relations == true ){
						foreach (array_keys($value->getRelations()) as $relKey => $relValue ){
							$data['data'][$key]->{$relValue} = $this->BulidJson($value->{$relValue} , get_class($value->{$relValue}) );
						}
					}

				}
			}
			$url = Input::getUri();
			$data['pagination'] = $this->paginationMaker($url , $object->count() , $object->total() , $object->lastPage() , $object->currentPage() , $object->getPageName() );
			return $data;
		}

		if($object instanceof  $class ){
			try{
				$columns = $this->getColumns($object->getTable());

				foreach ($columns as $arrKey => $arrValue ){
					$data[$arrValue] = $object->{$arrValue};
				}
				if( $relations == true ){
					foreach (array_keys($object->getRelations()) as $relKey => $relValue ){
						$data[$relValue] = $this->BulidJson($object->{$relValue} , get_class($object->{$relValue}) ) ;
					}
				}

				return $data ;
			}catch (\Exception $e){
				try{
					foreach ($object as $key => &$value ){
						$data[$key] = new \stdClass();
						$columns = $this->getColumns($value->getTable());
						foreach ($columns as $arrKey => $arrValue ){
							$data[$key]->{$arrValue} = $value->{$arrValue} ?? '' ;
							if( $relations == true ){
								foreach (array_keys($value->getRelations()) as $relKey => $relValue ){
									$data[$key]->{$relValue} = $this->BulidJson($value->{$relValue} , get_class($value->{$relValue}) );
								}
							}

						}
					}
					return $data;
				}catch (\Exception $e ){

				}
			}

		}
	}

	private function remove_query_string_var($url, $key) {
		if(strpos($key , $url) == false){
			$url = preg_replace('/(.*)(?)' . $key . '=[^&]+?(?)[0-9]{0,4}(.*)|[^&]+&(&)(.*)/i', '$1$2$3$4$5$6$7$8$9$10$11$12$13$14$15$16$17$18$19$20', $url . '&');
			$url = substr($url, 0, -1);
			return $url ;
		}else{
			return $url;
		}
	}

	private function paginationMaker($uri , $count , $total , $lastPage , $currentPage , $pageName = false  ){
		if($pageName != false ){
			$pageparam = $pageName;
		}else{
			$pageparam = 'page';
		}
		$data = new \stdClass();
		$data->count = $count;
		$data->total_count = $total;
		$data->current_page = $currentPage;
		$data->last_page = $lastPage;
		$next = $currentPage + 1;
		$prev = $currentPage - 1;
		$newUrl = $this->remove_query_string_var($uri, "$pageparam");
		if(preg_match('/(&)/' , $newUrl) != 0 || strpos($newUrl , '?') != false ){
			$separator = '&';
		}else{
			$separator = '?';
		}

		if($currentPage !=  $lastPage ){
			$link = str_replace('&&' , '&' , $newUrl . $separator. "$pageparam=". $next);
			$link = str_replace('?&' , '?' , $link);
			$data->next = $link;
			if($currentPage == 1){
				$data->prev = "";
			}else{
				$link = str_replace('&&' , '&' , $newUrl . $separator. "$pageparam=". $prev);
				$link = str_replace('?&' , '?' , $link);
				$data->prev = $link ;
			}
		}else{
			$data->next = "";
			if($currentPage == 1){
				$data->prev = "";
			}else{
				$link = str_replace('&&' , '&' , $newUrl . $separator. "$pageparam=". $prev);
				$link = str_replace('?&' , '?' , $link);
				$data->prev = $link ;
			}
		}
		return $data;
	}


}
