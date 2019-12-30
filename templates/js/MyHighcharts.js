// JavaScript Document
Highcharts.theme = {
    colors: ['#f45b5b', '#8085e9', '#8d4654', '#7798BF', '#aaeeee',
        '#ff0066', '#eeaaee', '#55BF3B', '#DF5353', '#7798BF', '#aaeeee'],
}
Highcharts.setOptions(Highcharts.theme);
Highcharts.setOptions({
    global: {
        useUTC: false
    }
});
Chart=function(options,series)
{
    this.chartId=1;
    this.yAxisMake=function(yAxis)
        {
            if(!yAxis)
            data={
                crosshair: {
                    enabled: true,
                    width: 1,
                    color: '#a08080',
                },            
                title: {
                    text: ' '
                },
                gridLineWidth: 1,
                gridLineColor: '#d0d0d0',
                tickPixelInterval : 20,
            };
            else
                {
                    data=yAxis;
                    for(var i=0;i<data.length;i++)
                        {
                            data[i].crosshair={
                                enabled: true,
                                width: 1,
                                color: '#a08080',
                            };
                            data[i].gridLineWidth= 1;
                            data[i].gridLineColor= '#d0d0d0';
                            data[i].tickPixelInterval = 20;
                        }
                }
            return data;
        }
    this.param=
        {
            credits : {
                    enabled: false
            },                               
            chart: {
                type: options.chartType,
                renderTo: 'container'+this.chartId,            
                zoomType:'x',
                marginBottom:150,
                marginTop:50,
                backgroundColor :'#EBE4D6',
            },
            
            title: {
                text: options.title.text+'',
                style: { "fontWeight" : "bold",  "fontSize": "12px"}
            },
            xAxis: {
                crosshair: {
                    enabled: true,
                    width: 1,
                    color: '#a08080',
                },
                type: 'datetime',            
                categories: '', 
                labels: {
                    style: { "fontSize":"10", "textOverflow": "none", "whiteSpace": "nowrap" },
                    rotation: -90,
                },
                title: {
                    text: options.xAxis.title+'',
                },
                gridLineWidth: 1,
                gridLineColor: '#d0d0d0',
                tickInterval: 60000,
            },
            yAxis: this.yAxisMake(options.yAxis),

                plotOptions: {
                areaspline: {
                    shadow: {
                        enabled:true,
                        color: '#000000',
                        offsetX: -1,
                        offsetY: 1,
                        width: 1,
                    },
                },
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    },
                },
                series:{
				    lineWidth:1.5,
				    marker: { enabled :false },
                    fillOpacity: 0.5,
            	},
            },
            tooltip :{
                split:true,
                shared: true,
                distance: 30,
                padding :5,
                backgroundColor: '#DFD5BD',
                borderColor:'#9E7D5B',
                shadow :{
                    enabled: true,
                    width: 8,
                    opacity: 0.1,
                },
        useHTML: options.useHTML,
            },                        
            series: series,
        }
        if(options.useHTML)
            {
                this.param.tooltip.headerFormat= '<table>';
                this.param.tooltip.pointFormat= '<tr> '+
                    '<tr><th>czas :</th><td>{point.name}</td></tr>' +
                    '<tr><th>wartość:</th><td>{point.y}</td></tr>',
                this.param.tooltip.footerFormat= '</table>';
                this.param.tooltip.followPointer= true;
            }
    this.Make=function() { 
        var chart = new Highcharts.Chart(this.param);
    }
}   