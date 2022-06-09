## Path Apı

* POST
  `/api/login`

Response success status code : 200

| Request Body | description                               |
|--------------|-------------------------------------------|
| username     | customer email  (required)                |
| password     | customer password (required)              |   

-----

* GET
  `/api/order/list`

Response success status code : 200


-----

* GET
  `/api/order/detail/{id}`

Response success status code : 200

| query parameters | description                               |
|------------------|-------------------------------------------|
| id               | order id                                  |


- sample usage<br>
  `/api/order/detail/1`

-----
* POST
  `/api/order/add`

Response success status code : 201

| request body | description | sample     |
| ---------------- | ------------ |------------|
| orderCode       | required | Code001    |
| quantity     | required | 10,5       |
| address     | required | istanbul   |
| shippingDate     | required | 2022-10-07 |
| productId     | required | 4,2        |

Note : Can specify multiple products and their quantities for an order by separating them with a comma. The length of the string in which you specify the product and quantity must be equal.

-----
* PUT
  `/api/order/update/{id}`

Response success status code : 200

| request body | description | sample     |
| ---------------- | ------------ |------------|
| quantity     | required | 10,5       |
| address     | required | istanbul   |
| productId     | required | 4,2        |

Note-1 : Orders past the shipping date cannot be updated.

Note-2 : Fill in the fields you want updated. Both need to be filled together when updating product or quantity. Specify the amount as zero for the product you want to cancel.

-----
* DELETE
  `/api/order/delete/{id}`

Response success status code : 204