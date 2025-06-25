import sys
from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Core.IFSelect import IFSelect_RetDone
from OCC.Core.BRep import BRep_Tool
from OCC.Core.TopLoc import TopLoc_Location
from OCC.Core.BRepMesh import BRepMesh_IncrementalMesh
from OCC.Core.ShapeFix import ShapeFix_Shape
from OCC.Extend.TopologyUtils import TopologyExplorer
from OCC.Core.TopoDS import topods
from OCC.Core.TopAbs import TopAbs_REVERSED


def fix_and_orient_shape(shape):
    """
    Fix and orient the entire shape to ensure it is closed and all faces point outward.

    Parameters:
    shape (TopoDS_Shape): The shape to fix.

    Returns:
    TopoDS_Shape: The fixed and oriented shape.
    """
    shape_fix = ShapeFix_Shape(shape)
    shape_fix.Perform()  # Fix the shape
    return shape_fix.Shape()


def step_to_obj_with_highlight(step_file, face_indices, output_obj_file):
    """
    Convert a STEP file to an OBJ file, highlighting specific faces with a different color.

    Parameters:
    step_file (str): Path to the input STEP file.
    face_indices (list of int): Indices of the faces to highlight (1-based index).
    output_obj_file (str): Path to the output OBJ file.
    """
    # Determine the MTL file name based on the OBJ file name
    output_mtl_file = output_obj_file.replace('.obj', '.mtl')

    # Read the STEP file
    step_reader = STEPControl_Reader()
    status = step_reader.ReadFile(step_file)
    if status != IFSelect_RetDone:
        print("Error: Unable to read STEP file.")
        return

    step_reader.TransferRoot()
    shape = step_reader.Shape()

    # Fix and orient the shape
    fixed_shape = fix_and_orient_shape(shape)

    # Explore the faces in the fixed shape
    topo_explorer = TopologyExplorer(fixed_shape)
    faces = list(topo_explorer.faces())

    if any(index < 1 or index > len(faces) for index in face_indices):
        print(f"Error: Some face indices are out of range. The STEP file contains {len(faces)} faces.")
        return

    # Highlight the specified faces
    highlight_faces = {faces[index - 1] for index in face_indices}  # Convert 1-based indices to 0-based

    # Generate mesh for the shape
    BRepMesh_IncrementalMesh(fixed_shape, 0.1)

    # Create the MTL file and define materials
    with open(output_mtl_file, 'w') as mtl_file:
        mtl_file.write("newmtl default_material\n")
        mtl_file.write("Kd 0.8 0.8 0.8\n")  # Default gray color
        mtl_file.write("\n")
        mtl_file.write("newmtl highlight_material\n")
        mtl_file.write("Kd 1.0 0.0 0.0\n")  # Red color for highlighted faces
        mtl_file.write("\n")

    objectIndex = 1        
    # Write the OBJ file and link the MTL file
    with open(output_obj_file, 'w') as obj_file:
        obj_file.write(f"mtllib {output_mtl_file}\n")  # Link the MTL file
        vertex_offset = 1  # Keep track of vertex indices
        for face in faces:
            # Determine the material
            if face in highlight_faces:
                material = "highlight_material"
            else:
                material = "default_material"

            # Get triangulation for the face
            loc = TopLoc_Location()
            triangulation = BRep_Tool.Triangulation(topods.Face(face), loc)
            if triangulation is None:
                continue

            # Write vertices
            vertices = []
            for i in range(1, triangulation.NbNodes() + 1):
                node = triangulation.Node(i).Transformed(loc.Transformation())
                vertices.append((node.X(), node.Y(), node.Z()))
                obj_file.write(f"v {node.X()} {node.Y()} {node.Z()}\n")

            # Apply the material
            obj_file.write(f"o Object{objectIndex}\n")
            obj_file.write(f"usemtl {material}\n")

            # Write faces (triangles) in the correct order
            for i in range(1, triangulation.NbTriangles() + 1):
                triangle = triangulation.Triangle(i)
                n1, n2, n3 = triangle.Get()

                # Ensure proper winding order
                if face.Orientation() == TopAbs_REVERSED:
                    n1, n2, n3 = n3, n2, n1

                # Write the face
                obj_file.write(f"f {vertex_offset + n1 - 1} {vertex_offset + n2 - 1} {vertex_offset + n3 - 1}\n")

            vertex_offset += triangulation.NbNodes()

    print(f"Successfully wrote OBJ file to {output_obj_file}.")
    print(f"Successfully wrote MTL file to {output_mtl_file}.")


if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python script.py <input_step_file> <comma_separated_face_indices> <output_obj_file>")
        sys.exit(1)

    input_step_file = sys.argv[1]
    face_indices = [int(idx.strip()) for idx in sys.argv[2].split(',')]
    output_obj_file = sys.argv[3]

    step_to_obj_with_highlight(input_step_file, face_indices, output_obj_file)
