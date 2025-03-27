import sys
import numpy as np
from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Core.TopExp import TopExp_Explorer
from OCC.Core.TopAbs import TopAbs_FACE
from OCC.Core.BRepAdaptor import brepgprop_SurfaceProperties #BRepAdaptor_Surface
from OCC.Core.GeomAbs import GeomAbs_Cylinder, GeomAbs_Plane
from OCC.Core.Bnd import Bnd_Box
from OCC.Core.BRepBndLib import brepbndlib_Add

def load_step_file(file_path):
    """Carrega o arquivo STEP e retorna a geometria"""
    step_reader = STEPControl_Reader()
    status = step_reader.ReadFile(file_path)
    
    if status != 1:
        raise ValueError("Erro ao abrir o arquivo STEP.")
    
    step_reader.TransferRoot()
    shape = step_reader.Shape()
    
    return shape

def analyze_geometry(shape):
    """ Analisa características geométricas para classificar a peça """
    explorer = TopExp_Explorer(shape, TopAbs_FACE)
    
    cylinder_count = 0
    plane_count = 0
    dominant_cylinder_axis = None

    while explorer.More():
        face = explorer.Current()
        surf = BRepAdaptor_Surface(face)
        surf_type = surf.GetType()

        if surf_type == GeomAbs_Cylinder:
            cylinder_count += 1
            cylinder = surf.Cylinder()
            axis = cylinder.Axis().Direction()
            
            # Se houver um eixo dominante, verificamos a consistência
            if dominant_cylinder_axis is None:
                dominant_cylinder_axis = axis
            elif not np.allclose([axis.X(), axis.Y(), axis.Z()], 
                                 [dominant_cylinder_axis.X(), dominant_cylinder_axis.Y(), dominant_cylinder_axis.Z()], atol=0.1):
                plane_count += 1  # Pode ser um chanfro ou uma área não cilíndrica significativa

        elif surf_type == GeomAbs_Plane:
            plane_count += 1

        explorer.Next()
    
    return cylinder_count, plane_count, dominant_cylinder_axis

def get_bounding_box(shape):
    """ Calcula a caixa delimitadora da peça para obter dimensões gerais """
    bbox = Bnd_Box()
    brepbndlib_Add(shape, bbox, True)
    xmin, ymin, zmin, xmax, ymax, zmax = bbox.Get()
    width = xmax - xmin
    depth = ymax - ymin
    height = zmax - zmin
    return width, depth, height

def classify_part(file_path):
    """Classifica a peça com base em suas características geométricas"""
    shape = load_step_file(file_path)
    cylinder_count, plane_count, dominant_axis = analyze_geometry(shape)
    width, depth, height = get_bounding_box(shape)

    # Consideramos que peças de torno têm um diâmetro dominante maior que a altura
    max_dimension = max(width, depth)
    min_dimension = min(width, depth, height)

    if cylinder_count > plane_count and max_dimension > height * 1.5:
        return "TORNO"
    elif plane_count > cylinder_count:
        return "FRESA"
    else:
        return "MISTO (Torno + Fresa)"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python classify_part.py arquivo.step")
        sys.exit(1)
    
    file_path = sys.argv[1]
    classification = classify_part(file_path)
    print(f"Classificação da peça: {classification}")
    
