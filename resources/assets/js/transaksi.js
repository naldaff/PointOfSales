import Vue from 'vue'
import axios from 'axios'
import VueSweetalert2 from 'vue-sweetalert2';

Vue.filter('currency', function(money){
	return accounting.formatMoney(money, "Rp ", 0, ".")
})

//use sweetalert
Vue.use(VueSweetalert2);

new Vue({
	el: '#ris',
	data: {
		product: {
			id: '',
			qty: '',
			price: '',
			name: '',
			photo: ''
		},
		//menambahkan cart
		cart:{
			product_id: '',
			qty: '1'
		},
		customer: {
			email: ''
		},
		//untuk menampung list cart
		shoppingCart: [],
		submitCart: false,
		formCustomer: false,
		reslutStatus: false,
		submitForm: false,
		errorMessage: '',
		message: ''
	},
	watch:{
		//apabila nilai dari product->id berubah maka
		'product.id': function(){
			//mengecek jika nilai dari product->id ada
			if(this.product.id){
				//maka akan menjalankan methods getProduct
				this.getProduct()
			}
		},
		'customer.email': function(){
			this.formCustomer = false
			if(this.customer.name != ''){
				this.customer = {
					name: '',
					phone: '',
					address: ''
				}
			}
		}
	},
	//menggunakan library select2 ketika file ini di-load
	mounted() {
        $('#product_id').on('change', () => {
            //apabila terjadi perubahan nilai yg dipilih maka nilai tersebut 
            //akan disimpan di dalam var product > id
            this.product.id = $('#product_id').val();
        });
    },
	methods:{
		getProduct(){
			//fetch ke server menggunakan axios dengan mengirimkan parameter id
			//dengan url /api/product/{id}
			axios.get(`/api/product/${this.product.id}`)
            .then((response) => {
                //assign data yang diterima dari server ke var product
                this.product = response.data
            })
		},

		//method untuk menambahkan product yang dipilih ke dalam cart
		addToCart(){
			this.submitCart = true;

			//send data ke server
			axios.post('/api/cart', this.cart)
			.then((response) => {
				setTimeout(() => {
					//apabila berhasil data disimpan ke dalam var shoppingCart
					this.shoppingCart = response.data

					//mengosongkan var
					this.cart.product_id = ''
					this.cart.qty = 1
					this.product = {
						id: '',
						price: '',
						name: '',
						photo: ''
					} 
					$('#product_id').val('')
					this.submitCart = false
				}, 2000)
			})
			.catch((error) => {

			})
		},

		//mengambil list cart yang telah disimpan
		getCart(){
			//fetch data ke server
			axios.get('/api/cart')
			.then((response) => {
				//data yang diterima disimpan ke dalam var shoppingCart
				this.shoppingCart = response.data
			})
		},

		//menghapus cart
		removeCart(id) {
            this.$swal({
				title: 'Kamu Yakin?',
				text: 'Kamu Tidak Dapat Mengembalikan Tindakan Ini!',
				type: 'warning',
				showCancelButton: true,
				confirmButtonText: 'Iya, Lanjutkan!',
				cancelButtonText: 'Tidak, Batalkan!',
				showCloseButton: true,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        setTimeout(() => {
                            resolve()
                        }, 2000)
                    })
                },
                allowOutsideClick: () => !this.$swal.isLoading()
			}).then ((result) => {
				if (result.value) {
					axios.delete(`/api/cart/${id}`)
					.then ((response) => {
						this.getCart();
					})
					.catch ((error) => {
						console.log(error);
					})
				}
			})
        },

		searchCustomer(){
			axios.post('api/customer/search', {
				email: this.customer.email
			})
			.then((response) => {
				if(response.data.status == 'success'){
					this.customer = response.data.data
					this.reslutStatus = true
				}
				this.formCustomer = true
			})
			.catch((error) => {

			})
		},

		sendOrder(){
			
		}
	}

})