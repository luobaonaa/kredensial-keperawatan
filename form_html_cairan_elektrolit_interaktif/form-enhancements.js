(function () {
  const root = document.getElementById("wordDocument"); if (!root || root.querySelector(".ai-summary-card")) return;
  const clean = s => (s || "").replace(/\s+/g, " ").trim();
  // Hapus kotak textarea kecil yang ikut terbawa dari konversi dokumen Word.
  root.querySelectorAll("table textarea").forEach(field => field.remove());
  const names = ["Pilih nama penguji","Ns. Ahmad Fauzi, S.Kep.","Ns. Siti Rahma, S.Kep.","Ns. Budi Santoso, S.Kep."];
  root.querySelectorAll("td,th").forEach(cell => {
    const label = clean(cell.textContent).toLowerCase();
    if (label.includes("asesor") && !label.includes("tanda tangan")) {
      const field = cell.querySelector("input.form-control-line") || cell.nextElementSibling?.querySelector("input.form-control-line");
      if (field && !field.dataset.assessorDone) {
        const select = document.createElement("select"); select.className="form-control-line assessor-select"; select.name=field.name||"nama-penguji";
        names.forEach((name,i) => { const o=document.createElement("option"); o.value=i?name:""; o.textContent=name; o.disabled=!i; o.selected=!i; select.appendChild(o); });
        field.replaceWith(select); select.dataset.assessorDone="1";
      }
    }
    if (/bukti[- ]?bukti pendukung|bukti pendukung/i.test(label)) { const row=cell.closest("tr"); if(row) row.style.display="none"; cell.style.display="none"; }
  });
  root.querySelectorAll("textarea").forEach(el => { if (/bukti pendukung/i.test(el.placeholder||"")) el.closest("td,th")?.style.setProperty("display","none"); });
  const card=document.createElement("section"); card.className="ai-summary-card";
  card.innerHTML='<h3>✨ Rangkuman & penilaian otomatis</h3><p>Rangkuman ini dibuat dari isian form sebagai perkiraan awal. Penguji tetap menjadi penentu nilai akhir.</p><textarea id="aiSummary" class="form-textarea" placeholder="Rangkuman otomatis akan muncul setelah ada jawaban..."></textarea><div class="score-row"><div class="score-box"><label for="aiScore">Nilai perkiraan (0–100)</label><input id="aiScore" name="nilai-perkiraan-ai" type="number" min="0" max="100" readonly></div><span class="ai-note">Dihitung dari kelengkapan jawaban.</span></div>';
  root.appendChild(card); const summary=card.querySelector("#aiSummary"), score=card.querySelector("#aiScore");
  function update(){ const answers=[...root.querySelectorAll("textarea")].filter(x=>x!==summary&&!x.closest(".ai-summary-card")&&clean(x.value)); const choices=root.querySelectorAll('input[type="radio"]:checked').length; const total=[...root.querySelectorAll("textarea")].filter(x=>x!==summary&&!x.closest(".ai-summary-card")).length; score.value=total?Math.min(100,Math.round(((answers.length+choices)/(total+Math.max(choices,1)))*100)):0; const topics=answers.slice(0,5).map(x=>clean(x.value).slice(0,110)).join("; "); summary.value=answers.length?`Peserta telah mengisi ${answers.length} bagian jawaban${choices?` dan memilih ${choices} opsi penilaian`:""}. Fokus asesmen: ${topics}. Jawaban tetap perlu ditinjau penguji untuk memastikan prosedur, keselamatan pasien, dan standar kompetensi.`:"Belum ada jawaban yang dapat dirangkum. Silakan isi form terlebih dahulu."; }
  root.addEventListener("input",update); root.addEventListener("change",update); update();
})();
