import os
import urllib.request
import cv2
import numpy as np
import httpx
from fastapi import FastAPI, File, UploadFile, BackgroundTasks

app = FastAPI(title="AI Attendance & Drowsiness Engine")

LARAVEL_API_URL = "http://127.0.0.1:8000/api/internal/attendance-log"

# Load CascadeClassifier untuk deteksi Wajah & Mata
def load_cascade(filename):
    cascade_path = os.path.join(os.getcwd(), filename)
    if not os.path.exists(cascade_path):
        url = f"https://raw.githubusercontent.com/opencv/opencv/master/data/haarcascades/{filename}"
        urllib.request.urlretrieve(url, cascade_path)
    return cv2.CascadeClassifier(cascade_path)

face_cascade = load_cascade('haarcascade_frontalface_default.xml')
eye_cascade = load_cascade('haarcascade_eye.xml')

async def send_log_to_laravel(log_data: dict):
    """Mengirim data hasil deteksi AI ke Laravel secara async di background"""
    async with httpx.AsyncClient() as client:
        try:
            response = await client.post(LARAVEL_API_URL, json=log_data)
            print(f"[SYNC LARAVEL] Status: {response.status_code}, Respon: {response.json()}")
        except Exception as e:
            print(f"[SYNC LARAVEL ERROR] Gagal mengirim log ke Laravel: {e}")

@app.get("/")
def index():
    return {"status": "online", "message": "AI Engine is running"}

@app.post("/api/v1/detect-drowsiness")
async def detect_drowsiness(background_tasks: BackgroundTasks, file: UploadFile = File(...)):
    try:
        contents = await file.read()
        nparr = np.frombuffer(contents, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if frame is None:
            return {"status": "ERROR", "message": "Frame gambar tidak valid"}

        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))
        
        if len(faces) == 0:
            return {"status": "NO_FACE_DETECTED", "message": "Wajah tidak terdeteksi di kamera"}

        (x, y, w, h) = faces[0]
        roi_gray = gray[y:y+h, x:x+w]
        
        eyes = eye_cascade.detectMultiScale(roi_gray, scaleFactor=1.1, minNeighbors=5, minSize=(15, 15))
        
        total_eyes_detected = int(len(eyes))
        is_fatigue = bool(total_eyes_detected < 2)
        recommendation = "WARNING_FATIGUE" if is_fatigue else "FIT_FOR_WORK"

        # Data yang akan dikirim ke Laravel API
        laravel_log = {
            "user_code": "USR-001",
            "user_name": "Muhammad Asla Labiib Badaly",
            "eyes_detected": total_eyes_detected,
            "is_fatigue": is_fatigue,
            "recommendation": recommendation
        }

        # Kirim log ke Laravel secara background
        background_tasks.add_task(send_log_to_laravel, laravel_log)

        return {
            "status": "SUCCESS",
            "metrics": {
                "eyes_detected": total_eyes_detected,
                "is_fatigue": is_fatigue
            },
            "recommendation": recommendation,
            "synced_to_laravel": True
        }
    except Exception as e:
        return {"status": "ERROR", "message": str(e)}