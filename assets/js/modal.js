// Modal untuk preview gambar ukuran penuh
function openImageModal(imageSrc) {
    // Cek apakah modal sudah ada
    let modal = document.getElementById('imageModal');
    if (!modal) {
        // Buat modal baru
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: pointer;
        `;
        
        const img = document.createElement('img');
        img.id = 'modalImage';
        img.style.cssText = `
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        `;
        
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '✕';
        closeBtn.style.cssText = `
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        `;
        
        modal.appendChild(img);
        modal.appendChild(closeBtn);
        document.body.appendChild(modal);
        
        // Tutup modal saat diklik
        modal.onclick = function(e) {
            if (e.target === modal || e.target === closeBtn) {
                modal.style.display = 'none';
            }
        };
    }
    
    const modalImg = document.getElementById('modalImage');
    modalImg.src = imageSrc;
    modal.style.display = 'flex';
}