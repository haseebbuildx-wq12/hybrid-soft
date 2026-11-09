@extends('layouts.app')
@section('title', __( 'lang_v1.sales_order'))
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.sales_order')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

                {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
                {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('so_list_filter_status',  __('sale.status') . ':') !!}
                {!! Form::select('so_list_filter_status', $sales_order_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        @if(!empty($shipping_statuses))
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('so_list_shipping_status', __('lang_v1.shipping_status') . ':') !!}
                    {!! Form::select('so_list_shipping_status', $shipping_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
        @endif
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" id="load_form_btn" class="tw-dw-btn tw-dw-btn-info tw-dw-btn-sm tw-text-white">
                    <i class="fa fa-list"></i> Load Form
                </button>
                @can('so.create')
                    <a class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white" href="{{action([\App\Http\Controllers\SellController::class, 'create'])}}?sale_type=sales_order">
                    <i class="fa fa-plus"></i> @lang('lang_v1.add_sales_order')</a>
                @endcan
            </div>
        @endslot
        @if( auth()->user()->can('so.view_own') || auth()->user()->can('so.view_all'))
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                <thead>
                    <tr>
                        <th class="not-export">@lang('messages.action')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('restaurant.order_no')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('lang_v1.contact_no')</th>
                        <th>@lang('sale.location')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('lang_v1.shipping_status')</th>
                        <th>@lang('lang_v1.quantity_remaining')</th>
                        <th>@lang('lang_v1.added_by')</th>
                    </tr>
                </thead>
            </table>
        </div>
        @endif
    @endcomponent
    <div class="modal fade edit_pso_status_modal" tabindex="-1" role="dialog"></div>

    <!-- Load Form Modal -->
    <div class="modal fade" id="load_form_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Load Form</h4>
                </div>
                <div class="modal-body" id="load_form_body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="load_form_print">Print / Save as PDF</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
@stop
@section('javascript')
@includeIf('sales_order.common_js')
<script type="text/javascript">
$(document).ready( function(){
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
    });
    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        aaSorting: [[1, 'desc']],
        "ajax": {
            "url": '/sells?sale_type=sales_order',
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }

                if($('#sell_list_filter_location_id').length) {
                    d.location_id = $('#sell_list_filter_location_id').val();
                }
                d.customer_id = $('#sell_list_filter_customer_id').val();

                if ($('#so_list_filter_status').length) {
                    d.status = $('#so_list_filter_status').val();
                }
                if ($('#so_list_shipping_status').length) {
                    d.shipping_status = $('#so_list_shipping_status').val();
                }

                if($('#created_by').length) {
                    d.created_by = $('#created_by').val();
                }
            }
        },
        columnDefs: [ {
            "targets": 7,
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'action', name: 'action'},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'conatct_name', name: 'conatct_name'},
            { data: 'mobile', name: 'contacts.mobile'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'status', name: 'status'},
            { data: 'shipping_status', name: 'shipping_status'},
            { data: 'so_qty_remaining', name: 'so_qty_remaining', "searchable": false},
            { data: 'added_by', name: 'u.first_name'},
        ]
    });
    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by, #so_list_filter_status, #so_list_shipping_status',  function() {
        sell_table.ajax.reload();
    });
});

// ================= LOAD FORM =================
$(document).on('click', '#load_form_btn', async function () {
    $('#load_form_modal').modal('show');
    $('#load_form_body').html('<p>Loading orders...</p>');

    try {
        // Table ke current filters ke sath tamam orders (sirf 25 nahi)
        const params = Object.assign({}, sell_table.ajax.params(), { start: 0, length: -1 });
        const list = await $.ajax({ url: '/sells?sale_type=sales_order', data: params });

        const rows = list.data || [];
        const totals = {};
        const orderNos = [];
        let done = 0;

        let fetched = 0, noLink = 0;

        const processRow = async function (row) {
            // Order ka View link: pehle DT_RowAttr, warna Action column se
            let href = row.DT_RowAttr && row.DT_RowAttr['data-href'];
            if (!href) {
                const m = String(row.action || '').match(/\/sells\/(\d+)/);
                if (m) href = '/sells/' + m[1];
            }
            const inv = $('<div>').append($.parseHTML(row.invoice_no || '')).text().trim();
            if (!href) { noLink++; return; }
            orderNos.push(inv);

            let html;
            try { html = await $.get(href); } catch (err) { return; }
            fetched++;
            const $doc = $('<div>').append($.parseHTML(String(html)));

            $doc.find('table').each(function () {
                let $hdr = $(this).find('thead th');
                if (!$hdr.length) $hdr = $(this).find('tr').first().find('th,td');
                const headers = $hdr.map(function () {
                    return $(this).text().replace(/\s+/g, ' ').trim().toLowerCase();
                }).get();
                const pIdx = headers.findIndex(h => h.indexOf('product') !== -1);
                let qIdx = headers.findIndex(h => h === 'quantity');
                if (qIdx === -1) qIdx = headers.findIndex(h => h.indexOf('quantity') !== -1 && h.indexOf('remaining') === -1);
                if (pIdx === -1 || qIdx === -1) return;

                $(this).find('tr').has('td').each(function () {
                    const tds = $(this).find('td');
                    const name = $(tds[pIdx]).text().replace(/\s+/g, ' ').trim();
                    const qtyText = $(tds[qIdx]).text().replace(/,/g, '');
                    const m = qtyText.match(/-?\d+(\.\d+)?/);
                    const qty = m ? parseFloat(m[0]) : 0;
                    if (name && qty) totals[name] = (totals[name] || 0) + qty;
                });
            });

            done++;
            $('#load_form_body').html('<p>Loading... ' + done + ' / ' + rows.length + '</p>');
        };

        // 5-5 orders ek sath fetch hon taake tez ho
        for (let i = 0; i < rows.length; i += 5) {
            await Promise.all(rows.slice(i, i + 5).map(processRow));
        }

        const names = Object.keys(totals).sort();
        let out = '<p><strong>Sales Orders (' + orderNos.length + '):</strong> ' + orderNos.join(', ') + '</p>';
        out += '<table class="table table-bordered table-striped"><thead><tr><th>#</th><th>Product Name</th><th>Total Qty</th></tr></thead><tbody>';
        names.forEach(function (n, i) {
            out += '<tr><td>' + (i + 1) + '</td><td>' + n + '</td><td>' + (Math.round(totals[n] * 100) / 100) + '</td></tr>';
        });
        out += '</tbody></table>';
        if (!names.length) out = '<p>Koi product nahi mila.</p><p><small>Orders in list: ' + rows.length + ', link nahi mila: ' + noLink + ', pages fetch hue: ' + fetched + '</small></p>';
        $('#load_form_body').html(out);
    } catch (e) {
        console.error(e);
        $('#load_form_body').html('<p class="text-danger">Error aa gaya. Browser console (F12) check karo.</p>');
    }
});

$(document).on('click', '#load_form_print', function () {
    const w = window.open('', '_blank');
    w.document.write('<html><head><title>Load Form</title>' +
        '<style>body{font-family:Arial;padding:20px}table{border-collapse:collapse;width:100%}' +
        'th,td{border:1px solid #333;padding:6px 10px;text-align:left}</style></head><body>' +
        '<h2>Load Form</h2>' + $('#load_form_body').html() + '</body></html>');
    w.document.close();
    w.focus();
    w.print();
});
</script>

@endsection