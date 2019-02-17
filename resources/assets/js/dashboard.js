import Vue from 'vue';
import axios from 'axios';
import Chart from 'chart.js';

new Vue({
	el: '#ris',
	data: {
		//format data yang akan digunakan ke chart.js
		risChartData: {
			//Type chart-nya line
			type: 'line',
			data: {
				//yang perlu diperhatikan bagian label ini nilainya dinamis
				labels: [],
				datasets: [
					{
						label: 'Total Penjualan',
						//dan nilai data juga dinamis tergantung data yang diterima dari server
						data : [],
						backgroundColor: [
							'rgba(71, 183,132,.5)',
                            'rgba(71, 183,132,.5)',
                            'rgba(71, 183,132,.5)',
                            'rgba(71, 183,132,.5)',
                            'rgba(71, 183,132,.5)',
                            'rgba(71, 183,132,.5)',
                            'rgba(71, 183,132,.5)'
						],
						borderColor: [
							'#47b784',
                            '#47b784',
                            '#47b784',
                            '#47b784',
                            '#47b784',
                            '#47b784',
                            '#47b784'
						],
						borderWidth: 3
					}
				]
			},
			options: {
				responsive: true,
				lineTension: 1,
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero: true,
							padding: 25,
						}
					}]
				}
			}
		}
	},
	mounted(){
		//ketika aplikasi di-load maka akan menjalankan method getData()
		this.getData();
		//dan method createChart() dengan parameter 'ris-chart' dan format dari risChartData
		this.createChart('ris-chart', this.risChartData);
	},
	methods: {
		//method createChart dengan permintaan 2 parameter
		createChart(chartId, chartData){
			//mencari elemen dengan ID sesuai dari parameter chartId
			const ctx = document.getElementById(chartId);
			//mendefinisakn chart.js
			const myChart = new Chart(ctx, {
				type: chartData.type,
				data: chartData.data,
				options: chartData.options,
			});
		},

		//method getData() untuk meminta data dari server
		getData(){
			//mengirimkan permintaan dengan endpoint /api/chart
			axios.get('/api/chart')
			//kemudian response nya
			.then((response) => {
				//di-looping dengan memisahkan key dan value
				Object.entries(response.data).forEach(
					([key, value]) => {
						//dimana key (baca: dalam hal ini index data adalah tanggal)
						//kita masukan ke dalam risChartData > data > labels
						this.risChartData.data.labels.push(key);
						//KEMUDIAN VALUE DALAM HAL INI TOTAL PESANAN
                        //KITA MASUKKAN KE DALAM risChartData > data > datasets[0] > data
                        this.risChartData.data.datasets[0].data.push(value);	
					}
				);
			})
		} 
	}
})