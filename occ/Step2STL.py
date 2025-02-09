import sys
import os
from pprint import pprint

# referencia em: https://github.com/godeye/python-occ-step-to-stl/blob/master/step2stl.py

def read_step(filename):
    from OCC.Core.STEPControl import STEPControl_Reader
    from OCC.Core.IFSelect import IFSelect_RetDone, IFSelect_ItemsByEntity

    step_reader = STEPControl_Reader()
    status = step_reader.ReadFile(filename)
    if status == IFSelect_RetDone:
        failsonly = False
        #step_reader.PrintCheckLoad(failsonly, IFSelect_ItemsByEntity)
        #step_reader.PrintCheckTransfer(failsonly, IFSelect_ItemsByEntity) 

        ok = step_reader.TransferRoot(1)
        _nbs = step_reader.NbShapes()
        return step_reader.Shape(1)
    else:
        raise ValueError('Cannot read the file')

def write_stl(shape, filename, definition=0.01):
    from OCC.Core.StlAPI import StlAPI_Writer
    import os

    directory = os.path.split(__name__)[0]
    stl_output_dir = os.path.abspath(directory)
    assert os.path.isdir(stl_output_dir)

    stl_file = os.path.join(stl_output_dir, filename)

    stl_writer = StlAPI_Writer()
    #stl_writer.SetASCIIMode(False)
    stl_writer.SetASCIIMode(True)

    from OCC.Core.BRepMesh import BRepMesh_IncrementalMesh
    mesh = BRepMesh_IncrementalMesh(shape, definition)
    mesh.Perform()
    assert mesh.IsDone()

    stl_writer.Write(shape, stl_file)
    assert os.path.isfile(stl_file)
    return stl_file

pathSTEP = sys.argv[1]
pathSTL = sys.argv[2]

shape = read_step(pathSTEP)


#print vars(shape)
write_stl(shape, pathSTL)

print("passei shape salvo")
#pprint(globals())
#pprint(locals())
#pprint(object.__dict__)
