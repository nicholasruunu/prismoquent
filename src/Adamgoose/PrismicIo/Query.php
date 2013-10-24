<?php namespace Adamgoose\PrismicIo;

use Prismic\Api;
use Illuminate\Support\Collection;

class Query {

  protected $model;

  /**
   * Creates a new Query instance.
   *
   * @param  \Adamgoose\PrismicIo\Model $model
   * @return void
   */
  public function __construct(Model $model)
  {
    $this->model = $model;
  }

  /**
   * Sets the ref of the query
   *
   * @param  string  $ref
   * @return \Adamgoose\PrismicIo\Query
   */
  public function ref($ref)
  {
    $this->model->ref = $ref;

    return $this;
  }

  /**
   * Sets the collection of the query
   *
   * @param  string  $collection
   * @return \Adamgoose\PrismicIo\Query
   */
  public function collection($collection)
  {
    $this->model->collection = $collection;

    return $this;
  }

  /**
   * Sets the mask of the query
   *
   * @param  string  $mask
   * @return \Adamgoose\PrismicIo\Query
   */
  public function mask($mask)
  {
    $this->model->mask = $mask;

    return $this;
  }

  /**
   * Sets the tags of the query
   *
   * @param  array  $tags
   * @return \Adamgoose\PrismicIo\Query
   */
  public function tags(array $tags)
  {
    $this->model->tags = $tags;

    return $this;
  }

  /**
   * Appends an "at" predicated query
   *
   * @param  string $key
   * @param  string $value
   * @return \Adamgoose\Prismic\Query
   */
  public function at($key, $value)
  {
    $this->model->conditions['at'][] = array(
      'key' => $key,
      'value' => $value,
    );

    return $this;
  }

  /**
   * Appends an "any" predicated query
   *
   * @param  string $key
   * @param  array  $values
   * @return \Adamgoose\Prismic\Query
   */
  public function any($key, array $values)
  {
    $this->model->conditions['any'][] = array(
      'key' => $key,
      'values' => $values,
    );

    return $this;
  }

  /**
   * Alias for at('document.id', $id)
   *
   * @param  string $id
   * @return \Prismic\Document
   */
  public function find($id)
  {
    $collection = $this->get();

    $collection = $collection->filter(function($document) use ($id)
    {
      if($document->id == $id) return true;
    });

    return $collection->first();
  }

  /**
   * Return the document with matching slug
   *
   * @param  string $slug
   * @return \Prismic\Document
   */
  public function findSlug($slug)
  {
    $collection = $this->get();

    $collection = $collection->filter(function($document) use ($slug)
    {
      if($document->containsSlug($slug)) return true;
    });

    return $collection->first();
  }

  /**
   * Alias for get()->first();
   *
   * @return \Prismic\Document
   */
  public function first()
  {
    return $this->get()->first();
  }

  /**
   * Execute the query
   *
   * @return \Illuminate\Support\Collection
   */
  public function get()
  {
    $api = $this->prepareApi();

    $query = '';

    // Set mask using predicated query
    if($this->model->mask != null)
      $query .= '[:d = at(document.type, "'.$this->model->mask.'")]';

    // Set tags using predicated query
    if($this->model->tags != null)
      $query .= '[:d = any(document.tags, ["'.implode('","', $this->model->tags).'"])]';

    // Set "at" predicated queries
    if($this->model->conditions['at'] != null)
      foreach($this->model->conditions['at'] as $at) {
        $query .= '[:d = at('.$at['key'].', "'.$at['value'].'")]';
      }

    // Set "any" predicated queries
    if($this->model->conditions['any'] != null)
      foreach($this->model->conditions['any'] as $any) {
        $query .= '[:d = any('.$any['key'].', ["'.implode('","', $any['values']).'"])]';
      }

    if($this->model->collection != null)
      $form = $api->forms()->{$this->model->collection};
    else
      $form = $api->forms()->everything;

    $form = $form->ref($this->getRef($api));

    if($query != '')
      $form = $form->query("[$query]");

    $results = $form->submit();

    return new Collection($results);
  }

  /**
   * Prepare API for calls
   *
   * @return \Prismic\Api
   */
  private function prepareApi()
  {
    return Api::get(
      $this->model->endpoint,
      $this->model->token
    );
  }

  /**
   * Returns either the master ref, or the defined ref
   *
   * @return string
   */
  private function getRef(Api $api)
  {
    if($this->model->ref != null)
      return $this->model->ref;

    return $api->master()->getRef();
  }

}