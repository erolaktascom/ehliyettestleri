<div>
    <x-admin.flash-messages />
    <!-- Report Metrics Header -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-7">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Tamamlanan Sınav</span>
            <div class="text-3xl font-black text-slate-900 mt-2">{{ number_format($finishedAttempts) }}</div>
            <span class="text-xs text-slate-400 font-semibold mt-1 block">Tüm seviyeler</span>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Geçme Oranı</span>
            <div class="text-3xl font-black text-emerald-600 mt-2">%{{ $passRate }}</div>
            <span class="text-xs text-slate-400 font-semibold mt-1 block">70+ puan alanlar</span>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Genel Puan Ortalaması</span>
            <div class="text-3xl font-black text-blue-600 mt-2">%{{ $averageScore }}</div>
            <span class="text-xs text-slate-400 font-semibold mt-1 block">100 üzerinden</span>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Soru Havuzu</span>
            <div class="text-3xl font-black text-purple-600 mt-2">{{ number_format($questions) }}</div>
            <span class="text-xs text-slate-400 font-semibold mt-1 block">Aktif soru sayısı</span>
        </div>
    </div>

    <!-- Topics Performance Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-7">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="font-extrabold text-sm text-slate-900">Konulara Göre Başarı Dağılımı</h3>
                <p class="text-xs text-slate-500">Müfredat konularının adaylar tarafından doğru cevaplanma oranları</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5">Konu Başlığı</th>
                        <th class="px-6 py-3.5">Cevaplanan Soru</th>
                        <th class="px-6 py-3.5">Doğru Cevap</th>
                        <th class="px-6 py-3.5">Başarı Yüzdesi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topicStats as $ts)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5 font-bold text-slate-900">{{ $ts->topic }}</td>
                            <td class="px-6 py-3.5 font-semibold text-slate-700">{{ number_format($ts->total) }}</td>
                            <td class="px-6 py-3.5 font-semibold text-emerald-600">{{ number_format($ts->correct) }}</td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-32 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div style="width: {{ $ts->success_rate }}%" class="h-full bg-blue-600 rounded-full"></div>
                                    </div>
                                    <span class="text-xs font-extrabold text-slate-800">%{{ $ts->success_rate }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400 font-medium">Henüz yeterli cevap verisi oluşmadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Log Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <h3 class="font-extrabold text-sm text-slate-900">Günlük Sınav Aktivitesi Tablosu</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Tarih</th>
                        <th class="px-6 py-3">Tamamlanan Sınav</th>
                        <th class="px-6 py-3">Günün Ortalaması</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($dailyStats as $row)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3 font-semibold text-slate-700">{{ $row['date'] }}</td>
                            <td class="px-6 py-3 font-bold text-slate-900">{{ $row['attempts'] }}</td>
                            <td class="px-6 py-3">
                                <span class="font-bold text-xs px-2.5 py-1 rounded-md bg-slate-100 text-slate-700">
                                    %{{ $row['avg_score'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
